from flask import Flask, jsonify
from flask_cors import CORS  # Allow frontend to access backend
from mainconnection import connected  # Your database connection module

app = Flask(__name__)
CORS(app)  # Enable Cross-Origin Resource Sharing (CORS)


def get_transactions():
    """Fetch transactions from database."""
    conn = connected()  # Open a new connection for every request

    if not conn or not conn.is_connected():
        return []  # Return empty list if connection fails

    try:
        cursor = conn.cursor(dictionary=True)
        sql = "SELECT Amount, MpesaReceiptNumber, PhoneNumber, TransactionDate FROM transactions"
        cursor.execute(sql)
        results = cursor.fetchall()
        cursor.close()
        return results  # Do not close conn yet

    except Exception as e:
        print(f"Database error: {e}")
        return []

    finally:
        if conn.is_connected():
            conn.close()  # Now close connection safely


@app.route('/get_transactions', methods=['GET'])
def fetch_transactions():
    """API endpoint to fetch transactions."""
    data = get_transactions()
    return jsonify(data)  # Return data as JSON


if __name__ == '__main__':
    app.run(debug=True)  # Runs on http://127.0.0.1:5000
