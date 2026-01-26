from playwright.sync_api import sync_playwright
import time
import os
import urllib.parse

BASE_URL = "http://localhost:8000"

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # 1. Login Page
        print("Navigating to Login...")
        page.goto(BASE_URL + "/login.php")
        page.screenshot(path="01_login.png")
        print("Captured 01_login.png")

        # 2. Student Flow
        print("Logging in as student...")
        page.fill("input[name='username']", "student")
        page.fill("input[name='password']", "student123")
        page.click("button[type='submit']")
        # wait for any redirect
        page.wait_for_load_state("networkidle")

        # Navigate to Registration
        page.goto(BASE_URL + "/modules/registrar/registration.php")

        # We expect Wizard Mode since DB was reset
        if page.locator("#regForm").is_visible():
            print("In Wizard Mode")
            page.screenshot(path="02_student_wizard_step1.png")
            print("Captured 02_student_wizard_step1.png")

            # Fill Wizard to submit
            # Step 1
            page.select_option("select[name='course_applied']", "B.Tech")
            page.fill("input[name='previous_marks']", "85%")
            page.click("button[onclick='nextStep(2)']")

            # Step 2
            page.fill("input[name='full_name']", "Test Student")
            page.fill("input[name='dob']", "2000-01-01")
            page.select_option("select[name='nationality']", "Indian")
            page.fill("textarea[name='address']", "123 Campus Road")
            page.click("button[onclick='nextStep(3)']")

            # Step 3
            page.fill("input[name='father_name']", "Mr. Father")
            page.click("button[onclick='nextStep(4)']")

            # Step 4
            page.fill("input[name='abc_id']", "123456789012")
            page.click("button[onclick='nextStep(5)']")

            # Step 5 - Uploads
            # Create dummy files
            with open("dummy.pdf", "wb") as f: f.write(b"%PDF-1.4 dummy")

            page.set_input_files("input[name='photo']", "dummy.pdf")
            page.set_input_files("input[name='signature']", "dummy.pdf")
            page.set_input_files("input[name='id_proof']", "dummy.pdf")
            page.set_input_files("input[name='previous_marksheet']", "dummy.pdf")

            page.check("input#declaration")
            page.click("button:has-text('Submit Application')")

            page.wait_for_url("**success=1")
            print("Submitted Form")

        # Now we should be in Dashboard mode
        page.goto(BASE_URL + "/modules/registrar/registration.php")
        page.screenshot(path="03_student_dashboard.png")
        print("Captured 03_student_dashboard.png")

        # Logout
        page.goto(BASE_URL + "/logout.php")

        # 3. Registrar Flow
        print("Login as Registrar...")
        page.goto(BASE_URL + "/login.php")
        page.fill("input[name='username']", "registrar")
        page.fill("input[name='password']", "registrar123")
        page.click("button[type='submit']")

        # Go to Queue
        page.goto(BASE_URL + "/modules/registrar/verification_list.php")
        page.screenshot(path="04_registrar_queue.png")
        print("Captured 04_registrar_queue.png")

        # Click View
        # Need to be specific because search button is also btn-primary
        view_profile_btn = page.locator("a[title='View Profile']").first
        if view_profile_btn.count() > 0:
            view_profile_btn.click()
            # This goes to view_student.php
            page.wait_for_selector("text=Student Profile")
            page.screenshot(path="05_registrar_profile_view.png")
            print("Captured 05_registrar_profile_view.png")

            # Go to Documents (Split Screen)
            # Find the link with text "View & Verify Documents" or check the code
            # In view_student.php: <a href="view_documents.php?id=..." class="btn btn-primary"><i class="fas fa-file-check me-2"></i>View & Verify Documents</a>

            # Get Student ID to construct URL or click
            current_url = page.url
            parsed = urllib.parse.urlparse(current_url)
            query_params = urllib.parse.parse_qs(parsed.query)
            student_id = query_params['id'][0]

            page.goto(BASE_URL + f"/modules/registrar/view_documents.php?id={student_id}")
            page.wait_for_selector("text=Uploaded Documents")
            page.screenshot(path="06_registrar_doc_verification.png")
            print("Captured 06_registrar_doc_verification.png")

            # Capture Printable View
            page.goto(BASE_URL + f"/modules/registrar/view_profile_printable.php?id={student_id}")
            page.screenshot(path="07_printable_application.png")
            print("Captured 07_printable_application.png")

        else:
            print("No students in queue!")

        browser.close()

if __name__ == "__main__":
    run()
