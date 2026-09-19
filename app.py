import os
from flask import Flask, render_template, request, redirect, url_for, session
from database import get_db, init_db

app = Flask(__name__)

app.secret_key = os.environ.get(
    "SECRET_KEY",
    "labr-secret-key-123"
)

init_db()


# =========================
# DASHBOARD
# =========================

@app.route("/")
def index():

    if "user_id" not in session:
        return redirect(url_for("login"))

    db = get_db()

    total_patients = db.execute(
        "SELECT COUNT(*) AS total FROM patients"
    ).fetchone()["total"]

    total_tests = db.execute(
        "SELECT COUNT(*) AS total FROM tests"
    ).fetchone()["total"]

    total_receipts = db.execute(
        "SELECT COUNT(*) AS total FROM receipts"
    ).fetchone()["total"]

    total_revenue = db.execute(
        "SELECT COALESCE(SUM(total), 0) AS total FROM receipts"
    ).fetchone()["total"]

    db.close()

    return render_template(
        "index.html",
        total_patients=total_patients,
        total_tests=total_tests,
        total_receipts=total_receipts,
        total_revenue=total_revenue
    )


# =========================
# LOGIN
# =========================

@app.route("/login", methods=["GET", "POST"])
def login():

    error = None

    if request.method == "POST":

        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")

        db = get_db()

        user = db.execute(
            """
            SELECT *
            FROM users
            WHERE username = ?
            AND password = ?
            """,
            (username, password)
        ).fetchone()

        db.close()

        if user:
            session["user_id"] = user["id"]
            session["username"] = user["username"]
            session["name"] = user["name"]

            return redirect(url_for("index"))

        error = "Invalid username or password!"

    return render_template(
        "login.html",
        error=error
    )


# =========================
# LOGOUT
# =========================

@app.route("/logout")
def logout():

    session.clear()

    return redirect(url_for("login"))


# =========================
# PATIENTS
# =========================

@app.route("/patients", methods=["GET", "POST"])
def patients():

    if "user_id" not in session:
        return redirect(url_for("login"))

    db = get_db()

    if request.method == "POST":

        name = request.form.get("name", "").strip()
        mr_no = request.form.get("mr_no", "").strip()
        mobile = request.form.get("mobile", "").strip()
        gender = request.form.get("gender", "").strip()
        age = request.form.get("age", "").strip()
        address = request.form.get("address", "").strip()

        try:

            db.execute(
                """
                INSERT INTO patients
                (name, mr_no, mobile, gender, age, address)
                VALUES (?, ?, ?, ?, ?, ?)
                """,
                (
                    name,
                    mr_no,
                    mobile,
                    gender,
                    age if age else None,
                    address
                )
            )

            db.commit()

        except Exception as e:

            db.rollback()
            db.close()

            return f"Error saving patient: {e}"

        db.close()

        return redirect(url_for("patients"))

    patient_list = db.execute(
        """
        SELECT *
        FROM patients
        ORDER BY id DESC
        """
    ).fetchall()

    db.close()

    return render_template(
        "patients.html",
        patients=patient_list
    )


# =========================
# DELETE PATIENT
# =========================

@app.route("/patients/delete/<int:patient_id>")
def delete_patient(patient_id):

    if "user_id" not in session:
        return redirect(url_for("login"))

    db = get_db()

    db.execute(
        "DELETE FROM patients WHERE id = ?",
        (patient_id,)
    )

    db.commit()
    db.close()

    return redirect(url_for("patients"))


# =========================
# RUN
# =========================

if __name__ == "__main__":

    port = int(os.environ.get("PORT", 8000))

    app.run(
        host="0.0.0.0",
        port=port
    )
