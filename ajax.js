document.getElementById('paymentForm').addEventListener('submit', async function (event) {
    event.preventDefault();

    let amount = document.getElementById('amount').value.trim();
    let phone = document.getElementById('phone').value.trim();
    let errorElement = document.getElementById('error');

  //  errorElement.textContent = "";
    errorElement.style.display = "block";

    if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
        errorElement.style.color = "red";
        errorElement.textContent = "Please enter a valid amount.";
        hideMessageAfterDelay();
        return;
    }

    if (!/^(07|01)\d{8}$/.test(phone)) {
        errorElement.style.color = "red";
        errorElement.textContent = "Please enter a valid 10-digit phone number (07XXXXXXXX or 01XXXXXXXX).";
        hideMessageAfterDelay();
        return;

    }

    let paymentData = {
        amount: parseFloat(amount),
        phone: `254${phone.slice(-9)}`
    };

    try {
        let response = await fetch("stk_initiate.php", {  // Ensure correct path
            method: "POST",  // Explicitly use POST
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(paymentData)
        });

        let result = await response.json();
        errorElement.style.color = "green";
        errorElement.textContent = result.success || result.error;

        hideMessageAfterDelay();

    } catch (error) {
        errorElement.style.color = "red";
        errorElement.textContent = "Error processing payment.";
        hideMessageAfterDelay();
    }
});

// Function to hide the message after 3 seconds
function hideMessageAfterDelay() {
    setTimeout(() => {
        document.getElementById('error').style.display = "none";
    }, 3000);
}
