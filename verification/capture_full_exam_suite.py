from playwright.sync_api import sync_playwright
import time

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # ==================== ADMIN FLOW ====================
        print("Starting Admin Flow...")

        # Login
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "admin")
        page.fill("input[name='password']", "admin123")
        page.click("button[type='submit']")
        page.wait_for_url("http://localhost:8000/")

        # 31. Exam Dashboard
        page.goto("http://localhost:8000/modules/exam/index.php")
        page.wait_for_selector("h2")
        page.screenshot(path="verification/31_exam_dashboard.png")
        print("Captured 31_exam_dashboard.png")

        # 32. Subject Management
        page.goto("http://localhost:8000/modules/exam/manage_subjects.php")
        page.screenshot(path="verification/32_manage_subjects.png")
        print("Captured 32_manage_subjects.png")

        # 33. Exam Creation
        page.goto("http://localhost:8000/modules/exam/manage_exams.php")
        page.screenshot(path="verification/33_manage_exams.png")
        print("Captured 33_manage_exams.png")

        # 34. Timetable
        page.goto("http://localhost:8000/modules/exam/timetable.php")
        page.screenshot(path="verification/34_timetable.png")
        print("Captured 34_timetable.png")

        # 35. Infrastructure (Just to show it exists in context)
        page.goto("http://localhost:8000/modules/infrastructure/index.php")
        page.screenshot(path="verification/35_infrastructure_summary.png")
        print("Captured 35_infrastructure_summary.png")

        # 36. Application Management
        page.goto("http://localhost:8000/modules/exam/manage_applications.php")
        page.screenshot(path="verification/36_manage_applications.png")
        print("Captured 36_manage_applications.png")

        # 37. Seating Plan
        page.goto("http://localhost:8000/modules/exam/seating_plan.php")
        # Click Generate if buttons exist, just to show state
        if page.is_visible("button[name='generate_seating']"):
             page.click("button[name='generate_seating']")
             page.wait_for_timeout(500)
        page.screenshot(path="verification/37_seating_plan_view.png")
        print("Captured 37_seating_plan_view.png")

        # 38. Invigilation
        page.goto("http://localhost:8000/modules/exam/invigilation.php")
        page.screenshot(path="verification/38_invigilation_roster.png")
        print("Captured 38_invigilation_roster.png")

        # 39. Reports (Desk Chits) - using ID 1 for Exam and Subject
        page.goto("http://localhost:8000/modules/exam/reports/desk_chits.php?exam_id=1&subject_id=1")
        # This might be a printable page, so screenshotting works fine
        page.screenshot(path="verification/39_reports_desk_chits.png")
        print("Captured 39_reports_desk_chits.png")

        # Logout
        page.goto("http://localhost:8000/logout.php")

        # ==================== STUDENT FLOW ====================
        print("Starting Student Flow...")

        # Login
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "student")
        page.fill("input[name='password']", "student123")
        page.click("button[type='submit']")
        page.wait_for_url("http://localhost:8000/")

        # 40. Student Dashboard (or Apply Exam)
        page.goto("http://localhost:8000/modules/exam/apply_exam.php")
        page.screenshot(path="verification/40_student_exam_dashboard.png")
        print("Captured 40_student_exam_dashboard.png")

        # 41. Admit Card
        page.goto("http://localhost:8000/modules/exam/admit_card.php")
        page.screenshot(path="verification/41_student_admit_card.png")
        print("Captured 41_student_admit_card.png")

        # 42. Results
        page.goto("http://localhost:8000/modules/exam/results.php")
        page.screenshot(path="verification/42_student_results.png")
        print("Captured 42_student_results.png")

        browser.close()

if __name__ == "__main__":
    run()
