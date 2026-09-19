import os
from flask import Flask, render_template, request, redirect, url_for, session

app = Flask(__name__)
app.secret_key = 'super_secret_labr_key_123'

@app.route('/')
@app.route("/patients", methods=["GET", "POST"])
def patients():

    if "user_id" not in session:
        return redirect("/login")

    db = get_db()

    if request.method == "POST":

        name = request.form.get("name")
        mr_no = request.form.get("mr_no")
        mobile = request.form.get("mobile")
        gender = request.form.get("gender")
        age = request.form.get("age")
        address = request.form.get("address")

        db.execute("""
            INSERT INTO patients
            (name, mr_no, mobile, gender, age, address)
            VALUES (?, ?, ?, ?, ?, ?)
        """, (name, mr_no, mobile, gender, age, address))

        db.commit()
        db.close()

        return redirect("/patients")

    patients = db.execute("""
        SELECT * FROM patients
        ORDER BY id DESC
    """).fetchall()

    db.close()

    return render_template("patients.html", patients=patients)


@app.route("/patients/delete/<int:patient_id>")
def delete_patient(patient_id):

    if "user_id" not in session:
        return redirect("/login")

    db = get_db()

    db.execute(
        "DELETE FROM patients WHERE id = ?",
        (patient_id,)
    )

    db.commit()
    db.close()

    return redirect("/patients")
def home():
    if 'user' not in session:
        return redirect(url_for('login'))
    return render_template('index.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        if username == "admin" and password == "1234":
            session['user'] = username
            return redirect(url_for('home'))
        else:
            error = "Invalid username or password!"
            
    return render_template('login.html', error=error)

@app.route('/logout')
def logout():
    session.pop('user', None)
    return redirect(url_for('login'))

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 8000))
    app.run(host='0.0.0.0', port=port)
