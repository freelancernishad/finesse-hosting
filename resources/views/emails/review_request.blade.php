<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Your Job Seekers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .header {
            background-color: #232f3e;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            padding: 20px;
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }
        .stars {
            font-size: 22px;
            color: #ffa41c;
            margin: 10px 0;
        }
        .job-seeker-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: left;
        }
        .btn {
            display: inline-block;
            background-color: #ffa41c;
            color: #ffffff;
            padding: 14px 20px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #ff8c00;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 14px;
            color: #777;
        }
        .footer a {
            color: #ffa41c;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">How was your experience?</div>

    <div class="content">
        <p>Hi <strong>{{ $name }}</strong>,</p>
        <p>Your event has been successfully completed! We’d love to hear your feedback on the job seekers who worked for you.</p>

        <p class="stars">⭐⭐⭐⭐⭐</p>

        @foreach ($jobSeekers as $jobSeeker)
        <div class="job-seeker-box">
            <strong>{{ $jobSeeker->name }}</strong><br>
            <span style="color: #666;">📞 {{ $jobSeeker->phone }}</span>
        </div>
        @endforeach

        <p>Click the button below to leave your review:</p>

        <a href="{{ url('/reviews') }}" class="btn">Leave a Review</a>

        <p>Thank you for choosing our service!</p>
    </div>

    <div class="footer">
        Need help? <a href="{{ url('/support') }}">Visit our support center</a> <br>
        © {{ date('Y') }} Your Company | All rights reserved
    </div>
</div>

</body>
</html>
