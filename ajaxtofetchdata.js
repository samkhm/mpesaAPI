async function fetchTransactions() {
    try {
        let response = await fetch("http://127.0.0.1:5000/get_transactions");  // Flask API URL
        let data = await response.json();

        let table = document.getElementById("tabledata");
        let message = document.querySelector(".message"); // Fixed message selector
        message.style.display = "none";  // Hide message if successful

        table.innerHTML = ""; // Clear existing data

        if (data.length === 0) {
            message.style.display = "block";
            message.textContent = "No transactions found.";
            return;
        }

        data.forEach((transaction, index) => {
            let row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${transaction.Amount}</td>
                    <td>${transaction.MpesaReceiptNumber}</td>
                    <td>${transaction.PhoneNumber}</td>
                    <td>${transaction.TransactionDate}</td>
                </tr>
            `;
            table.innerHTML += row;
        });
    } catch (error) {
        let message = document.querySelector(".message");
        message.style.color = "red";
        message.style.display = "block";
        message.textContent = "Error fetching data. Please try again.";
        console.error("Error fetching transactions:", error);
    }
}

fetchTransactions();  // Load data initially
setInterval(fetchTransactions, 5000);  // Refresh every 5 seconds
