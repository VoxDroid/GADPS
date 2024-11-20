<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'assets/html/disable-caching.html'; ?>
    <title>Contact - Gender and Development Profiling System</title>
    <?php include 'assets/html/icon_front.html'; ?>
    <?php include 'assets/html/styling_front.html'; ?>
    <style>
        .container {
            flex: 1;
            background: linear-gradient(145deg, #ffffff, #f6f7ff);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            width: 95%;
            max-width: 800px;
            margin: 2rem auto;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px 20px 0 0;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.5rem;
        }

        .contact-info {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .contact-info h2 {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 0.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .contact-item i {
            width: 30px;
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-right: 1rem;
        }

        .contact-item p {
            margin: 0;
            font-size: 1.1rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .social-links a {
            color: var(--primary-color);
            font-size: 2rem;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color);
            transform: scale(1.1);
        }

        .map-container {
            margin-top: 2rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 300px;
            border: none;
        }
    </style>
</head>
<body>
    <?php include 'assets/html/header_front.html'; ?>

    <div class="container">
        <h1>Contact Us</h1>
        
        <div class="contact-info">
            <h2>Contact Information::</h2>
            <div class="contact-item">
                <i class="fas fa-user"></i>
                <p>Mr/Ms. Client</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <p>client@email.com</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <p>+63 977 123 4567</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <p>San Roque Barangay Hall, 68HF+3F3, Victoria, Laguna</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <p>Office Hours: Monday to Friday, 8:00 AM - 5:00 PM</p>
            </div>
            
            <div class="social-links">
                <a href="https://www.facebook.com/lspuofficial" target="_blank"><i class="fab fa-facebook"></i></a>
                <a href="https://twitter.com/lspuofficial" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://www.linkedin.com/school/laguna-state-polytechnic-university" target="_blank"><i class="fab fa-linkedin"></i></a>
                <a href="https://www.instagram.com/lspuofficial" target="_blank"><i class="fab fa-instagram"></i></a>
            </div>
        </div>

        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4617.761621104658!2d121.32108857576112!3d14.227634986097724!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd5f63b39d6139%3A0x762dcbde4c90db8c!2sSan%20Roque%20Barangay%20Hall!5e1!3m2!1sen!2sph!4v1732087863048!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

    <?php include 'assets/html/footer.html'; ?>
</body>
</html>