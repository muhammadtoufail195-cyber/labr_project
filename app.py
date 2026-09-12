from flask import Flask, render_template, session, redirect, url_for
from config import Config, db, require_login, e

app = Flask(__name__)
app.config.from_object(Config)

# ڈیٹا بیس کو ایپ سے جوڑیں
db.init_app(app)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/dashboard')
@require_login  # یہ فنکشن چیک کرے گا کہ یوزر لاگ ان ہے یا نہیں
def dashboard():
    return render_template('dashboard.html')

@app.route('/login')
def login():
    return render_template('login.html')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)

