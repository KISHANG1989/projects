from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 720})
        page = context.new_page()

        try:
            # Login as Admin
            print("Navigating to Login...")
            page.goto("http://localhost:8000/login.php")
            page.fill("input[name='username']", "admin")
            page.fill("input[name='password']", "admin123")
            print("Submitting login...")
            page.click("button[type='submit']")
            page.wait_for_load_state('networkidle')
            print("Logged in as Admin.")

            # Navigate to Question Bank
            print("Navigating to Question Bank...")
            page.goto("http://localhost:8000/modules/exam/question_bank.php")
            page.screenshot(path="verification/43_question_bank_initial.png")

            # Add a Question
            print("Adding Question 1...")
            # We need to make sure options are loaded
            # Using index=1 (first option after placeholder)
            page.select_option("select[name='subject_id']", index=1)
            page.select_option("select[name='unit']", "1")
            page.fill("textarea[name='question_text']", "What is the capital of India?")
            page.select_option("select[name='question_type']", "Descriptive")
            page.click("button[name='add_question']")
            page.wait_for_load_state('networkidle')
            print("Added Question 1.")

            # Add another question to Unit 1
            print("Adding Question 2...")
            page.select_option("select[name='subject_id']", index=1)
            page.select_option("select[name='unit']", "1")
            page.fill("textarea[name='question_text']", "Explain Democracy.")
            page.click("button[name='add_question']")
            page.wait_for_load_state('networkidle')
            print("Added Question 2.")

            page.screenshot(path="verification/44_question_bank_filled.png")

            # Navigate to Generate Paper
            print("Navigating to Generate Paper...")
            page.goto("http://localhost:8000/modules/exam/generate_paper.php")

            # Use index=0 here because generate_paper.php might not have a placeholder option
            page.select_option("select[name='exam_id']", index=0)
            page.select_option("select[name='subject_id']", index=0)
            page.fill("input[name='paper_title']", "Test Paper 1")

            # Set Blueprint
            page.fill("input[name='unit1_count']", "2")
            page.fill("input[name='unit2_count']", "0")
            page.fill("input[name='unit3_count']", "0")
            page.fill("input[name='unit4_count']", "0")
            page.fill("input[name='unit5_count']", "0")

            print("Generating Paper...")
            page.click("button[name='generate']")
            page.wait_for_load_state('networkidle')

            page.screenshot(path="verification/45_generated_paper_result.png")
            print("Paper Generated.")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/debug_error.png")

        finally:
            browser.close()

if __name__ == "__main__":
    run()
