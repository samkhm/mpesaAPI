from mainconnection import connected

# Connect to the database
conn = connected()
cursor = conn.cursor()

if conn.is_connected():
    print("connected to databse")

# Define transaction details
Resultcode = 0
Amount = 400
MpesaReceiptNumber = "QUIDQWBDWI"
PhoneNumber = "0745891224"
TransactionDate = "2025-04-11"  # Use YYYY-MM-DD format for MySQL

# Correct SQL statement using placeholders
sql = """INSERT INTO transactions 
         (Resultcode, Amount, MpesaReceiptNumber, PhoneNumber, TransactionDate) 
         VALUES (%s, %s, %s, %s, %s)"""

# Execute query with values passed separately (prevents SQL injection)
values = (Resultcode, Amount, MpesaReceiptNumber, PhoneNumber, TransactionDate)
cursor.execute(sql, values)

# Commit the transaction to save changes
conn.commit()

# Check if insertion was successful
if cursor.rowcount > 0:
    print("✅ Data inserted successfully")
else:
    print("❌ Data failed to insert")

# Close cursor and connection
cursor.close()
conn.close()
