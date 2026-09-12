import os
from flask import session, redirect, url_for
from flask_sqlalchemy import SQLAlchemy

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY', 'your_secret_key_here')
    SQLALCHEMY_DATABASE_URI = 'mysql+pymysql://root:@localhost/labr'
    SQLALCHEMY_TRACK_MODIFICATIONS = False

db = SQLAlchemy()

def e(text):
    if text is None:
        return ''
    import html
    return html.escape(str(text))

def require_login(f):
    from functools import wraps
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

