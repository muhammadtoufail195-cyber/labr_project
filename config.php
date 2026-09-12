import os
from flask import session, redirect, url_for
from flask_sqlalchemy import SQLAlchemy

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY', 'your_secret_key_here')
    
    # -------------------------------------------------------------
    # لوکل ہوسٹ (Localhost) کے لیے نیچے والی سطر استعمال کریں:
    # -------------------------------------------------------------
    SQLALCHEMY_DATABASE_URI = 'mysql+pymysql://root:@localhost/labr'

    # -------------------------------------------------------------
    # اگر ngrok یا ریموٹ ڈیٹا بیس استعمال کرنا ہو تو یہ فارمیٹ ہوگا:
    # SQLALCHEMY_DATABASE_URI = 'mysql+pymysql://root:password@0.tcp.ngrok.io:12345/labr'
    # -------------------------------------------------------------

    SQLALCHEMY_TRACK_MODIFICATIONS = False

db = SQLAlchemy()

# HTML characters کو محفوظ بنانے کے لیے فنکشن (PHP کے e() کی جگہ)
def e(text):
    if text is None:
        return ''
    import html
    return html.escape(str(text))

# لاگ ان چیک کرنے کا فنکشن (PHP کے require_login() کی جگہ)
def require_login(f):
    from functools import wraps
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

