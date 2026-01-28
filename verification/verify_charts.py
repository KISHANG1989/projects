from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = f"file://{os.getcwd()}/AdminPro/html/charts.html"
        print(f"Loading: {file_path}")
        page.goto(file_path)

        # Wait for the dashboard to load (just in case)
        page.wait_for_selector(".topbar")

        # Take a screenshot
        screenshot_path = "verification/charts_v3.png"
        page.screenshot(path=screenshot_path, full_page=True)
        print(f"Screenshot saved to {screenshot_path}")

        browser.close()

if __name__ == "__main__":
    run()
