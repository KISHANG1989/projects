from playwright.sync_api import sync_playwright
import time

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 720})
        page = context.new_page()

        try:
            # 1. Admin Login & Malpractice Reporting
            print("1. Admin: Reporting Malpractice")
            page.goto("http://localhost:8000/login.php")
            page.fill("input[name='username']", "admin")
            page.fill("input[name='password']", "admin123")
            page.click("button[type='submit']")
            page.wait_for_load_state('networkidle')

            page.goto("http://localhost:8000/modules/exam/malpractice.php")
            # Fill Report
            page.fill("input[name='student_id']", "4") # Student ID 4
            page.select_option("select[name='exam_id']", index=1)
            page.select_option("select[name='subject_id']", index=1)
            page.fill("textarea[name='description']", "Caught with cheat sheet.")
            page.click("button[name='report_ufm']")
            page.wait_for_load_state('networkidle')

            page.screenshot(path="verification/46_malpractice_reported.png")
            print("Malpractice Reported.")

            # 2. Admin: Enter Marks for Student ID 4 (to enable reval)
            print("2. Admin: Entering Marks for Student ID 4")
            page.goto("http://localhost:8000/modules/exam/marks_entry.php")

            # Select Exam/Subject
            page.select_option("select[name='exam_id']", index=1)
            page.select_option("select[name='subject_id']", index=1)
            page.click("button[type='submit']") # Load List
            page.wait_for_load_state('networkidle')

            # Target specific inputs for Student ID 4
            # name="marks[4][internal]"
            internal_input = page.query_selector("input[name='marks[4][internal]']")
            external_input = page.query_selector("input[name='marks[4][external]']")

            if internal_input and external_input:
                internal_input.fill("30")
                external_input.fill("40")
                page.click("button[name='save_marks']")
                page.wait_for_load_state('networkidle')
                print("Marks Saved for Student ID 4.")
            else:
                print("Student ID 4 inputs not found! Dumping page content for debug.")
                with open("verification/debug_marks_entry.html", "w") as f:
                    f.write(page.content())
                # Fallback: Fill first available just in case ID 4 is not 4
                inputs = page.query_selector_all("input[type='number']")
                if len(inputs) >= 2:
                    print("Fallback: Entering marks for first student found.")
                    inputs[0].fill("30")
                    inputs[1].fill("40")
                    page.click("button[name='save_marks']")
                    page.wait_for_load_state('networkidle')

            # 3. Admin: Process Results
            print("3. Admin: Processing Results")
            page.goto("http://localhost:8000/modules/exam/process_results.php")

            # Select Exam
            page.select_option("select[name='exam_id']", index=1)

            # Setup Dialog Handler BEFORE click
            page.on("dialog", lambda dialog: dialog.accept())

            # Click Process Results
            page.click("button[name='process_results']")

            # Wait for processing (it might take a moment)
            page.wait_for_load_state('networkidle')
            time.sleep(2) # Extra wait for DB updates if any async behavior (though PHP is sync)

            page.screenshot(path="verification/47_results_processed.png")
            print("Results Processed.")

            # Logout Admin
            page.goto("http://localhost:8000/logout.php")

            # 4. Student: Request Revaluation
            print("4. Student: Requesting Revaluation")
            page.goto("http://localhost:8000/login.php")
            page.fill("input[name='username']", "student")
            page.fill("input[name='password']", "student123")
            page.click("button[type='submit']")
            page.wait_for_load_state('networkidle')

            page.goto("http://localhost:8000/modules/exam/revaluation_request.php")
            page.screenshot(path="verification/48_reval_eligible_list.png")

            # Click Apply Reval (First button)
            buttons = page.query_selector_all("button[name='request_reval']")
            if buttons:
                print(f"Found {len(buttons)} eligible subjects.")
                # Setup Dialog Handler
                page.on("dialog", lambda dialog: dialog.accept())
                buttons[0].click()
                page.wait_for_load_state('networkidle')
                print("Revaluation Requested.")
                page.screenshot(path="verification/49_reval_requested.png")
            else:
                print("No eligible subjects for revaluation found.")
                with open("verification/debug_reval_student.html", "w") as f:
                    f.write(page.content())

            # Logout Student
            page.goto("http://localhost:8000/logout.php")

            # 5. Admin: Manage Revaluation
            print("5. Admin: Managing Revaluation")
            page.goto("http://localhost:8000/login.php")
            page.fill("input[name='username']", "admin")
            page.fill("input[name='password']", "admin123")
            page.click("button[type='submit']")

            page.goto("http://localhost:8000/modules/exam/manage_revaluation.php")
            page.screenshot(path="verification/50_reval_manage.png")

            # Update first request
            inputs = page.query_selector_all("input[name='new_marks']")
            if inputs:
                inputs[0].fill("80") # Increase marks
                # Find the select associated with this row.
                # Assuming table structure, we need the select in the same row.
                # Simplest way: query all selects with name='status'
                selects = page.query_selector_all("select[name='status']")
                if selects:
                    selects[0].select_option("Completed")

                # Click update button
                update_buttons = page.query_selector_all("button[name='update_reval']")
                if update_buttons:
                    update_buttons[0].click()
                    page.wait_for_load_state('networkidle')
                    print("Revaluation Updated.")
                    page.screenshot(path="verification/51_reval_completed.png")
            else:
                 print("No revaluation requests to manage.")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/debug_error_post_exam.png")

        finally:
            browser.close()

if __name__ == "__main__":
    run()
