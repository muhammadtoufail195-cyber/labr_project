import os
from flask import Flask, render_template, request, redirect, url_for, session

app = Flask(__name__)
app.secret_key = 'super_secret_labr_key_123'

@app.route('/')
def home():
    # اگر صارف لاگ ان نہیں ہے تو سیدھا لاگ ان پیج شو کروائیں
    if 'user' not in session:
        return render_template('login.html')
    return render_template('index.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        if username == "admin" and password == "1234":
            session['user'] = username
            return redirect(url_for('dashboard'))
        else:
            error = "Invalid username or password!"
            
    return render_template('login.html', error=error)

@app.route('/dashboard')
def dashboard():
    if 'user' not in session:
        return redirect(url_for('home'))
    return render_template('index.html')

@app.route('/logout')
def logout():
    session.pop('user', None)
    return redirect(url_for('home'))

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 8000))
    app.run(host='0.0.0.0', port=port)
