# Registrar Module Roadmap (Production-Ready ERP)

This document outlines the comprehensive feature set required for a fully functional, enterprise-grade Registrar module in an Indian University ERP, compliant with NEP 2020.

## 1. Advanced Admission Management
- [ ] **Multi-Mode Admissions:**
    -   **Lateral Entry (Direct 2nd Year):** Handling Diploma holders entering directly into 3rd Semester.
    -   **International Students:** Visa details, distinct fee structures, FRRO compliance.
    -   **Migration/Transfer:** Students transferring from other universities with credit mapping.
    -   **PhD/Research Scholars:** Thesis tracking, guide allocation, doctoral committee meetings.
- [ ] **Merit List & Counseling:**
    -   Automated merit calculation based on 12th/Entrance scores.
    -   Seat matrix management (Reservation categories: SC/ST/OBC/EWS/PWD).
    -   Online Counseling and Seat Freezing/Floating.

## 2. Student Lifecycle & Status Management
- [x] **Core Statuses (Implemented):** Provisional, Admitted.
- [ ] **Extended Statuses:**
    -   **Active:** Currently attending classes.
    -   **Deactive/Semester Break:** Temporarily away.
    -   **Detained/Debarred:** Due to attendance shortage or disciplinary action.
    -   **Suspended/Expelled:** Disciplinary outcomes.
    -   **Alumni:** Successfully graduated.
- [ ] **Automated Progression:**
    -   Batch promotion (Year 1 -> Year 2) based on exam results.
    -   Section and Group allocation logic.

## 3. Academic Records & Certification (NEP 2020)
- [ ] **Academic Bank of Credits (ABC):**
    -   API integration with DigiLocker/ABC portal.
    -   Credit accumulation and transfer logging.
- [ ] **Subject Registration:**
    -   Elective selection (Generic Electives, Open Electives).
    -   Value Added Courses (VAC) and Skill Enhancement Courses (SEC) mapping.
- [ ] **Certification Engine:**
    -   **Bonafide Certificate:** Auto-generated for loans/passes.
    -   **Transfer Certificate (TC):** Upon leaving.
    -   **Migration Certificate:** For moving to another university.
    -   **Character Certificate.**
    -   **ID Card Generation:** Printable PDF templates with QR codes.

## 4. Regulatory Reporting & Compliance
- [ ] **UGC / AICTE Reports:** Automated data crunching for regulatory submissions.
- [ ] **AISHE (All India Survey on Higher Education):** Data export in required XML/Excel formats.
- [ ] **NIRF Data:** Student strength, PhD count, graduation outcomes.
- [ ] **NAAC Accreditation:** Data support for Criteria 2 (Teaching-Learning & Evaluation).

## 5. Fee & Scholarship Interface
- [ ] **Category-based Waivers:** Auto-apply discounts for Reserved Categories.
- [ ] **Scholarship Verification:** Linking with State/National Scholarship Portals.
- [ ] **Dues Clearance:** "No Dues" certificate generation from Library/Hostel before TC issuance.

## 6. Staff & Faculty Management (Registrar Scope)
- [ ] **Service Books:** Digital records of faculty employment history.
- [ ] **Leave Management:** Casual, Earned, Medical, Sabbatical tracking.
- [ ] **Duty Assignment:** Election duties, Exam duties.

## 7. Configuration & Master Data
- [ ] **Program/Course Management:** Define structure, duration, intake capacity.
- [ ] **Academic Calendar:** Define term start/end, exam dates, holidays.
