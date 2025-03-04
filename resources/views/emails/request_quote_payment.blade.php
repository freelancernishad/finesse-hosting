<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Quote Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #232f3e;
            color: #ffffff;
            text-align: center;
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .content {
            padding: 20px;
        }
        .order-summary {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border: 1px solid #ddd;
        }
        .order-summary p {
            margin: 8px 0;
            font-size: 14px;
            color: #333;
        }
        .payment-button {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #ff9900;
            color: #ffffff;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666666;
            padding: 15px;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            background-color: #f3f3f3;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .footer a {
            color: #0073e6;
            text-decoration: none;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .details-table th, .details-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            text-align: left;
        }
        .details-table th {
            background-color: #f3f3f3;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Header -->
        <div class="header">
            Confirm Your Request Quote
        </div>

        <!-- Content -->
        <div class="content">
            <p>Dear <strong>{{ $requestQuote->name }}</strong>,</p>
            <p>Thank you for your request! Below are the details of your event. Please complete the payment to confirm your booking.</p>

            <!-- Order Summary -->
            <div class="order-summary">
                <p><strong>Event Date:</strong> {{ date('F j, Y', strtotime($requestQuote->event_date)) }}</p>
                <p><strong>Start Time:</strong> {{ $requestQuote->start_time }}</p>
                <p><strong>Location:</strong> {{ $requestQuote->event_location }}</p>
                <p><strong>Number of Guests:</strong> {{ $requestQuote->number_of_guests }}</p>
                <p><strong>Budget:</strong> ${{ number_format($requestQuote->budget, 2) }}</p>
            </div>

            <!-- Categories Table -->
            <h3>Service Details</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(json_decode($requestQuote->categories, true) as $category)
                    <tr>
                        <td>{{ $category['name'] }}</td>
                        <td>{{ $category['count'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Payment Button -->
            <p style="margin-top: 20px;">To secure your booking, click the button below to complete your payment:</p>
            <a href="{{ $paymentLink }}" class="payment-button">Make Secure Payment</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Need help? Contact our support team at <a href="mailto:support@yourcompany.com">support@yourcompany.com</a></p>
            <p>Thank you for choosing <strong>Your Company Name</strong>.</p>
        </div>
    </div>

</body>
</html>
