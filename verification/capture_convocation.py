from playwright.sync_api import sync_playwright
import time

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # 1. Login as Registrar
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "registrar")
        page.fill("input[name='password']", "registrar123")
        page.click("button[type='submit']")

        # Wait for Dashboard
        page.wait_for_url("http://localhost:8000/")
        print("Logged in as Registrar")

        # 2. Navigate to Convocation
        # The link is in the navbar/sidebar under Registrar Office
        # Need to click the dropdown first
        page.click("text=Registrar Office")
        # Wait for animation/expansion
        page.wait_for_selector("text=Convocation", state="visible")
        page.click("text=Convocation")
        page.wait_for_url("**/modules/registrar/convocation/index.php")
        print("Navigated to Convocation Dashboard")

        # 3. Create Event
        # Check if button exists first
        page.wait_for_selector("button[data-bs-target='#createEventModal']")
        page.click("button[data-bs-target='#createEventModal']")
        page.wait_for_selector("#createEventModal", state="visible")

        page.fill("input[name='title']", "2024 Annual Convocation")
        page.fill("input[name='event_date']", "2024-12-15")
        page.fill("input[name='venue']", "University Main Auditorium")
        page.fill("input[name='batch_year']", "2020-2024")
        page.click("button[name='create_event']")

        # Wait for reload
        page.wait_for_load_state("networkidle")
        page.screenshot(path="verification/20_convocation_dashboard.png")
        print("Event Created & Dashboard captured")

        # 4. Manage Degrees
        # Click the first 'Manage Degrees' button
        page.click("a:has-text('Manage Degrees')")
        page.wait_for_url("**/manage_degrees.php*")

        # 5. Issue Degree
        page.click("#issue-new-tab")
        page.wait_for_selector("#issue-new.active")

        # Check if there are students
        if page.is_visible("text=No data available") or page.is_visible("text=No eligible students"):
            print("No students to issue degrees to.")
        else:
            # Click first "Issue Degree" button
            # Note: The button has onclick handler.
            page.click("button:has-text('Issue Degree')")
            page.wait_for_selector("#issueModal", state="visible")

            page.fill("input[name='program']", "B.Tech Computer Science")
            page.fill("input[name='cgpa']", "9.2")
            page.select_option("select[name='division']", "First Class with Distinction")

            page.click("button[name='issue_degree']")
            page.wait_for_load_state("networkidle")
            print("Degree Issued")

        page.screenshot(path="verification/21_convocation_manage.png")

        # 6. View Degree
        # Switch back to Issued tab (default)
        # Click "View Degree"
        # It opens in new tab (target=_blank)
        with context.expect_page() as new_page_info:
            page.click("a:has-text('View Degree')")

        degree_page = new_page_info.value
        degree_page.wait_for_load_state("networkidle")
        degree_page.screenshot(path="verification/22_degree_certificate.png")
        print("Degree Certificate captured")

        browser.close()

if __name__ == "__main__":
    run()
