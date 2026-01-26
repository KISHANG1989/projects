from playwright.sync_api import sync_playwright
import time

BASE_URL = "http://localhost:8000"

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # ==========================================
        # 1. ADMIN FLOW
        # ==========================================
        print("1. Admin Flow...")
        page.goto(BASE_URL + "/login.php")
        page.fill("input[name='username']", "admin")
        page.fill("input[name='password']", "admin123")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")

        # Dashboard
        page.goto(BASE_URL + "/modules/task_manager/index.php")
        page.wait_for_selector("h2")
        time.sleep(1) # Charts
        page.screenshot(path="verification/01_admin_dashboard.png")
        print("   Captured Admin Dashboard")

        # Create Task
        page.click("text=Create New Task")
        page.wait_for_selector("text=Create New Task")
        page.screenshot(path="verification/02_admin_create_task.png")
        print("   Captured Admin Create Task")

        # Task List
        page.goto(BASE_URL + "/modules/task_manager/task_list.php")
        page.screenshot(path="verification/03_admin_task_list.png")
        print("   Captured Admin Task List")

        # Logout
        page.goto(BASE_URL + "/logout.php")

        # ==========================================
        # 2. DEPT HEAD FLOW (CSE)
        # ==========================================
        print("2. Head Flow...")
        page.goto(BASE_URL + "/login.php")
        page.fill("input[name='username']", "head_cse")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")

        # Dashboard
        page.goto(BASE_URL + "/modules/task_manager/index.php")
        page.wait_for_selector("h2")
        time.sleep(1)
        page.screenshot(path="verification/04_head_dashboard.png")
        print("   Captured Head Dashboard")

        # Create Task (Specific to Dept)
        page.click("text=Create New Task")
        page.wait_for_selector("text=Create New Task")
        page.screenshot(path="verification/05_head_create_task.png")
        print("   Captured Head Create Task")

        # Logout
        page.goto(BASE_URL + "/logout.php")

        # ==========================================
        # 3. STAFF FLOW
        # ==========================================
        print("3. Staff Flow...")
        page.goto(BASE_URL + "/login.php")
        page.fill("input[name='username']", "staff_cse_1")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")

        # Dashboard
        page.goto(BASE_URL + "/modules/task_manager/index.php")
        page.wait_for_selector("h2")
        time.sleep(1)
        page.screenshot(path="verification/06_staff_dashboard.png")
        print("   Captured Staff Dashboard")

        # Task List
        page.goto(BASE_URL + "/modules/task_manager/task_list.php")
        page.screenshot(path="verification/07_staff_task_list.png")
        print("   Captured Staff Task List")

        # View Details of a Task
        # Click the first 'View' button or link
        page.locator(".btn-outline-primary").first.click()
        page.wait_for_selector("text=Collaboration")

        # Scroll down to show comments/actions
        page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        time.sleep(0.5)
        page.screenshot(path="verification/08_staff_view_task.png")
        print("   Captured Staff View Task")

        browser.close()

if __name__ == "__main__":
    run()
