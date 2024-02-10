<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Валидация -->
    <script>
        function validateForm() {
            var name = document.getElementById('name').value;
            var email = document.getElementById('email').value;
            var phone = document.getElementById('phone').value;
            var price = document.getElementById('price').value;

            if(name == "" || email == "" || phone == "" || price == "") {
                alert("Все поля должны быть заполнены");
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <form action="/api/create-lead" method="get" onsubmit="return validateForm()">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name"><br>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"><br>
        <label for="phone">Phone:</label><br>
        <input type="tel" id="phone" name="phone"> <br>
        <label for="price">Price:</label><br>
        <input type="number" id="price" name="price" min="0" step="1"><br>
        <input type="submit" value="Отправить">
    </form>
</body>
</html>