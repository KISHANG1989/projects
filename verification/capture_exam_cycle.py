from playwright.sync_api import sync_playwright
import time
import sqlite3

def setup_student_data():
    # Helper to ensure we have a valid student for the test without full registration flow
    conn = sqlite3.connect('database/erp.sqlite')
    cursor = conn.cursor()

    # 1. Create User
    cursor.execute("SELECT id FROM users WHERE username='exam_student'")
    res = cursor.fetchone()
    if not res:
        # hash for 'password'
        pw_hash = '$2y$10$wT6.w.j1.T1/1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1' # Dummy hash or use PHP to generate?
        # Actually, let's just use 'student' user if it exists, or create a simple one.
        # Let's use the existing 'student' user from setup.php if possible.
        pass

    # Let's clean up and insert a fresh student to be sure
    cursor.execute("INSERT OR IGNORE INTO users (username, password, role) VALUES ('exam_student', '$2y$10$8.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1', 'student')") # invalid hash but we won't login via PHP auth for this if we can avoid, or we use a known hash.
    # Wait, I need a valid password. 'student' / 'student123' exists.

    # Let's check if 'student' profile exists
    cursor.execute("SELECT id FROM users WHERE username='student'")
    user_id = cursor.fetchone()[0]

    # Clean up existing profiles to avoid duplicates (since schema lacks unique constraint on user_id)
    cursor.execute("DELETE FROM student_profiles WHERE user_id = ?", (user_id,))

    # Insert Profile for 'student'
    cursor.execute("INSERT INTO student_profiles (user_id, full_name, course_applied, current_semester, roll_number, enrollment_status) VALUES (?, 'Test Student', 'B.Tech Computer Science', 1, 'CS24001', 'Enrolled')", (user_id,))

    conn.commit()
    conn.close()

def run():
    setup_student_data()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # 1. Admin: Setup Exam
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "admin")
        page.fill("input[name='password']", "admin123")
        page.click("button[type='submit']")
        page.wait_for_url("http://localhost:8000/")

        # Add Subject
        page.goto("http://localhost:8000/modules/exam/manage_subjects.php")
        # Check if CS101 exists? Just add it, duplicate might fail or we ignore.
        # The form:
        page.click("button[data-bs-target='#addSubjectModal']")
        page.wait_for_selector("#addSubjectModal", state="visible")
        page.fill("input[name='subject_code']", "CS101-TEST")
        page.fill("input[name='subject_name']", "Intro to Testing")
        page.select_option("select[name='program_name']", "B.Tech Computer Science")
        page.select_option("select[name='semester']", "1")
        page.click("button[name='add_subject']")
        page.wait_for_timeout(1000) # Wait for reload

        # Add Exam
        page.goto("http://localhost:8000/modules/exam/manage_exams.php")
        page.click("button[data-bs-target='#addExamModal']")
        page.wait_for_selector("#addExamModal", state="visible")
        page.fill("input[name='name']", "Winter 2024 Test")
        page.fill("input[name='session']", "2024-25")
        page.select_option("select[name='program_name']", "B.Tech Computer Science")
        page.select_option("select[name='semester']", "1")
        page.select_option("select[name='status']", "Ongoing")
        page.click("button[name='add_exam']")
        page.wait_for_timeout(1000)

        # Add Timetable
        # Click the first "Timetable" button (usually the top one is the newest)
        page.click("a:has-text('Timetable')")
        page.wait_for_url("**/timetable.php*")

        # Find the row for CS101-TEST
        # We need to fill date/time.
        # Since table rows are dynamic, let's assume it's there.
        # We'll fill all inputs found.
        page.fill("input[type='date']", "2024-12-20")
        page.fill("input[name*='[start]']", "10:00") # Start
        page.fill("input[name*='[end]']", "13:00") # End
        page.click("button[name='save_timetable']")
        page.wait_for_timeout(1000)

        page.screenshot(path="verification/23_exam_setup.png")
        print("Admin setup complete")

        # Logout
        page.goto("http://localhost:8000/logout.php")

        # 2. Student: Admit Card
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "student")
        page.fill("input[name='password']", "student123")
        page.click("button[type='submit']")
        page.wait_for_url("http://localhost:8000/")

        # Check Sidebar link
        page.click("text=Admit Card")
        page.wait_for_url("**/admit_card.php")

        # There should be a "Winter 2024 Test" exam
        # Click Download
        # It opens or redirects? The current code adds query param.
        page.click("a:has-text('Winter 2024 Test')")
        page.wait_for_selector(".admit-card")
        page.screenshot(path="verification/24_admit_card.png")
        print("Admit Card verified")

        page.goto("http://localhost:8000/logout.php")

        # 3. Faculty: Marks Entry
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "faculty")
        page.fill("input[name='password']", "faculty123")
        page.click("button[type='submit']")

        page.click("text=Marks Entry")

        # Select Exam and Subject
        # We need to match the IDs. The selects are dynamic.
        # We can just select by label/text if possible, or index.
        # Select Exam (last one added is likely last in list? No, random order in select if not sorted)
        # manage_exams sorts by created_at DESC, but marks_entry sorts by what?
        # marks_entry: SELECT * FROM exams...

        # Let's try to select by Label text.
        page.select_option("select[name='exam_id']", label="Winter 2024 Test")
        # Subject
        page.select_option("select[name='subject_id']", label="CS101-TEST - Intro to Testing")

        page.click("button:has-text('Load Student List')")

        # Enter Marks for "Test Student" (roll CS24001)
        page.fill("input[name*='[internal]']", "35")
        page.fill("input[name*='[external]']", "55")
        page.click("button[name='save_marks']")
        page.wait_for_timeout(1000)

        page.screenshot(path="verification/25_marks_entry.png")
        print("Marks entered")

        page.goto("http://localhost:8000/logout.php")

        # 4. Student: Result
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='username']", "student")
        page.fill("input[name='password']", "student123")
        page.click("button[type='submit']")

        page.click("text=Results")
        # Need to select exam
        page.click("a:has-text('Winter 2024 Test')")

        # Verify marksheet
        page.wait_for_selector(".marksheet")
        page.screenshot(path="verification/26_marksheet.png")
        print("Marksheet verified")

        browser.close()

if __name__ == "__main__":
    run()
