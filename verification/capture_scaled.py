from playwright.sync_api import sync_playwright
import time

BASE_URL = "http://localhost:8000"

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        print("Capturing scaled UI...")
        page.goto(BASE_URL + "/login.php")
        page.fill("input[name='username']", "admin")
        page.fill("input[name='password']", "admin123")
        page.click("button[type='submit']")

        page.goto(BASE_URL + "/modules/task_manager/index.php")
        page.wait_for_selector("h2")
        time.sleep(1)
        page.screenshot(path="verification/16_scaled_ui.png")
        print("Captured 16_scaled_ui.png")

        browser.close()

if __name__ == "__main__":
    run()
