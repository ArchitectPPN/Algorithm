import sqlite3


def get_user(username):
    conn = sqlite3.connect("app.db")
    query = f"SELECT * FROM users WHERE name = '{username}'"
    return conn.execute(query).fetchall()
