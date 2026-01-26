from playwright.sync_api import sync_playwright

def generate_screenshots():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # 1. Login Page
        print("Capturing Login Page...")
        page.goto("http://localhost:8000/login.php")
        page.screenshot(path="verification/01_login_page.png", full_page=True)

        # 2. Student Flow
        print("Logging in as Student...")
        page.fill("input[name='username']", "student")
        page.fill("input[name='password']", "student123")
        page.click("button[type='submit']")
        page.wait_for_url("http://localhost:8000/")

        print("Capturing Student Dashboard...")
        page.screenshot(path="verification/02_student_dashboard.png", full_page=True)

        print("Capturing Admission Form...")
        page.click("text=Admission Form")
        page.wait_for_url("**/registration.php")
        page.screenshot(path="verification/03_admission_form.png", full_page=True)

        # Logout
        page.click("#navbarDropdown")
        page.click("text=Logout")

        # 3. Registrar Flow
        print("Logging in as Registrar...")
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "registrar")
        page.fill("input[name='password']", "registrar123")
        page.click("button[type='submit']")
        page.wait_for_url("http://localhost:8000/")

        print("Capturing Registrar Dashboard...")
        page.screenshot(path="verification/04_registrar_dashboard.png", full_page=True)

        print("Capturing Verification Queue...")
        page.click("text=Registrar Office") # Open submenu
        page.click("text=Verification Queue")
        page.wait_for_url("**/verification_list.php")
        page.screenshot(path="verification/05_verification_queue.png", full_page=True)

        print("Capturing Student Details...")
        # Assume there is at least one student from previous tests
        # Use first() locator
        view_btn = page.locator("text=View & Verify").first
        if view_btn.is_visible():
            view_btn.click()
            page.wait_for_url("**/view_student.php*")
            page.screenshot(path="verification/06_student_details.png", full_page=True)
        else:
            print("No student found to verify.")

        browser.close()
        print("Done.")

if __name__ == "__main__":
    generate_screenshots()
