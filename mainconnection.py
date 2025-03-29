import mysql.connector

def connected():
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="123456789",
            database="money_db"
        )

        if conn.is_connected():
            print("✅ Successfully connected to the database")
            return conn
    except mysql.connector.Error as e:
        print(f"❌ Database connection failed: {e}")
        return None