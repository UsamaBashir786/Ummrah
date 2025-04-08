from fpdf import FPDF

# Create PDF object
pdf = FPDF()
pdf.set_auto_page_break(auto=True, margin=15)
pdf.add_page()

# Set Title
pdf.set_font("Arial", 'B', 16)
pdf.cell(200, 10, txt="Usama's Unstoppable Schedule", ln=True, align='C')

# Add some spacing
pdf.ln(10)

# Add Introduction
pdf.set_font("Arial", '', 12)
pdf.multi_cell(0, 10, txt="""
This is Usama's customized schedule designed to maximize productivity, 
learning, and personal time. The goal is to make you unstoppable while 
balancing your job, learning MERN stack, and taking care of yourself.

Schedule Overview:
- Focused learning and project work in the morning before your office work.
- Post-work time dedicated to practice and personal time.
- Flexible night routine to wrap up the day and stay productive.

---
""")

# Add the actual schedule
pdf.set_font("Arial", '', 12)
pdf.multi_cell(0, 10, txt="""
**Morning (4:00 AM - 8:00 AM)**:
- 4:00 AM - 4:30 AM: Wake up, freshen up, light stretching or exercise.
- 4:30 AM - 5:30 AM: Learning Session (React.js, MERN stack).
- 5:30 AM - 6:00 AM: Breakfast (healthy food).
- 6:00 AM - 7:30 AM: Deep Work Session (MERN stack project work).
- 7:30 AM - 8:00 AM: Get ready for work.

**Work Hours (8:00 AM - 4:00 PM)**:
- 8:00 AM - 12:00 PM: Work focus (Pomodoro technique).
- 12:00 PM - 1:00 PM: Lunch and short walk.
- 1:00 PM - 4:00 PM: Finish work, tackle smaller tasks.

**Post-Work (4:00 PM - 8:00 PM)**:
- 4:00 PM - 5:00 PM: Relax, unwind (take a break, nap, walk).
- 5:00 PM - 6:30 PM: Learning/Project Work (MERN stack).
- 6:30 PM - 7:00 PM: Dinner (nutritious food).
- 7:00 PM - 8:00 PM: Personal time (hobbies, socializing).

**Night (8:00 PM - 1:00 AM)**:
- 8:00 PM - 10:00 PM: Project work or practice.
- 10:00 PM - 11:00 PM: Wind down, relax (read, watch a show).
- 11:00 PM - 12:30 AM: Learning/Review (MERN stack).
- 12:30 AM - 1:00 AM: Prep for bed, unwind.

**Sleep (1:00 AM - 4:00 AM)**:
- Sleep time for rest and recovery.
""")

# Save the PDF to a file
pdf_output = "c:\\xampp\\htdocs\\ummrah\\Usama_Unstoppable_Schedule.pdf"
pdf.output(pdf_output)

print(f"PDF saved to: {pdf_output}")
