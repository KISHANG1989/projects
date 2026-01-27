from playwright.sync_api import sync_playwright
import time

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 720})
        page = context.new_page()

        # 1. Register a new user for Lateral Entry
        page.goto("http://localhost:8000/login.php")

        # Helper to create unique user
        import random
        rnd = random.randint(1000, 9999)
        username = f"lateral_student_{rnd}"

        # Register User
        page.click("text=Register here")
        page.fill("input[name='username']", username)
        page.fill("input[name='password']", "password")
        # page.select_option("select[name='role']", "student") # Role is hidden/default
        page.click("button:has-text('Register')")
        page.wait_for_selector("text=Registration successful")
        page.click("text=Login Here")

        # Login
        page.wait_for_selector("input[name='username']")
        page.fill("input[name='username']", username)
        page.fill("input[name='password']", "password")
        page.click("button:has-text('Sign In')")

        # Dashboard -> Registrar
        page.click("text=Registrar")

        # Fill Form - Step 1
        page.wait_for_selector("#regForm")
        page.select_option("select[name='admission_mode']", "Lateral Entry")
        time.sleep(0.5)

        # Capture Lateral Entry Form Toggle
        page.screenshot(path="verification/17_lateral_entry_form.png")
        print("Captured Lateral Entry Form")

        page.select_option("select[name='course_applied']", "B.Tech")
        page.fill("input[name='previous_marks']", "88") # Diploma %
        if page.is_visible("input[name='diploma_reg_no']"):
            page.fill("input[name='diploma_reg_no']", "DIP123456")

        page.click("#step1 button:has-text('Next')")

        # Skip other steps for speed (just fill required)
        # Step 2
        page.click("#step2-tab")
        page.fill("input[name='full_name']", "Lateral Candidate")
        page.fill("input[name='dob']", "2002-05-15")
        page.fill("textarea[name='address']", "123 Diploma Street")
        page.click("#step2 button:has-text('Next')")

        # Step 3 (Optional) -> Next
        page.click("#step3-tab")
        page.click("#step3 button:has-text('Next')")

        # Step 4 (Optional) -> Next
        page.click("#step4-tab")
        page.click("#step4 button:has-text('Next')")

        # Step 5 - Uploads
        page.click("#step5-tab")
        # Upload dummy files
        # Create a dummy file
        with open("dummy.pdf", "wb") as f:
            f.write(b"%PDF-1.4 empty")

        page.set_input_files("input[name='photo']", "dummy.pdf")
        page.set_input_files("input[name='signature']", "dummy.pdf")
        page.set_input_files("input[name='id_proof']", "dummy.pdf")
        page.set_input_files("input[name='previous_marksheet']", "dummy.pdf")

        page.check("input[type='checkbox']")
        page.click("button:has-text('Submit Application')")
        page.wait_for_url("**/registration.php?success=1")

        # Logout
        page.goto("http://localhost:8000/logout.php")

        # 2. Login as Registrar
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "registrar")
        page.fill("input[name='password']", "registrar123")
        page.click("button:has-text('Sign In')")

        page.goto("http://localhost:8000/modules/registrar/verification_list.php")

        # Filter for Lateral (or just check existence)
        page.screenshot(path="verification/18_registrar_list_lateral.png")
        print("Captured Registrar List with Lateral Badge")

        # Click Verification (Find the new user)
        # Assuming it's at the top or we search
        page.fill("input[name='search']", "Lateral Candidate")
        page.click("button[type='submit']")

        page.wait_for_selector("a[title='View Profile']")
        page.click("a[title='View Profile']")

        # 3. Change Status
        page.select_option("select[name='status']", "Active")
        page.fill("input[name='remarks']", "Verified Diploma, Fees Paid.")
        page.click("form[action='update_status.php'] button")

        # Capture Status Change
        try:
            page.wait_for_selector(".badge:has-text('Active')", timeout=5000)
        except:
            print("Timeout waiting for Active badge. Taking debug screenshot.")
            page.screenshot(path="verification/debug_status_fail.png")
            # Print URL
            print("Current URL:", page.url)
            raise

        page.screenshot(path="verification/19_status_updated.png")
        print("Captured Status Updated")

        browser.close()

if __name__ == "__main__":
    run()
