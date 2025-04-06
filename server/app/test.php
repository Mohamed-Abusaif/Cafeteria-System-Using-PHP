<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>نموذج تسجيل مستخدم جديد</title>
  <style>
      body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          background-color: #f4f4f9;
      }

      .form-container {
          background-color: #fff;
          padding: 30px;
          border-radius: 8px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
          width: 100%;
          max-width: 500px;
      }

      h2 {
          text-align: center;
          margin-bottom: 20px;
      }

      .form-group {
          margin-bottom: 15px;
      }

      .form-group label {
          display: block;
          font-size: 16px;
          margin-bottom: 8px;
      }

      .form-group input {
          width: 100%;
          padding: 10px;
          font-size: 14px;
          border: 1px solid #ccc;
          border-radius: 4px;
      }

      .form-group input[type="file"] {
          padding: 0;
      }

      .form-group button {
          width: 100%;
          padding: 12px;
          background-color: #4CAF50;
          color: white;
          border: none;
          border-radius: 4px;
          font-size: 16px;
          cursor: pointer;
      }

      .form-group button:hover {
          background-color: #45a049;
      }

      .error-message {
          color: red;
          font-size: 14px;
          text-align: center;
      }
  </style>
</head>
<body>

<div class="form-container">
  <h2>تسجيل مستخدم جديد</h2>
  <form action="../controllers/user.controller.php" method="POST" enctype="multipart/form-data">
    <!-- حقل الاسم -->
    <div class="form-group">
      <label for="name">الاسم</label>
      <input type="text" id="name" name="name" required>
    </div>

    <!-- حقل البريد الإلكتروني -->
    <div class="form-group">
      <label for="email">البريد الإلكتروني</label>
      <input type="email" id="email" name="email" required>
    </div>

    <!-- حقل كلمة المرور -->
    <div class="form-group">
      <label for="password">كلمة المرور</label>
      <input type="password" id="password" name="password" required>
    </div>

    <!-- حقل تأكيد كلمة المرور -->
    <div class="form-group">
      <label for="confirm_password">تأكيد كلمة المرور</label>
      <input type="password" id="confirm_password" name="confirm_password" required>
    </div>

    <!-- حقل رقم الغرفة -->
    <div class="form-group">
      <label for="roomNo">رقم الغرفة</label>
      <input type="text" id="roomNo" name="roomNo" required>
    </div>

    <!-- حقل رفع الصورة -->
    <div class="form-group">
      <label for="image">الصورة</label>
      <input type="file" id="image" name="image" accept="image/*" required>
    </div>

    <!-- زر إرسال البيانات -->
    <div class="form-group">
      <button type="submit">تسجيل المستخدم</button>
    </div>

    <!-- عرض الرسائل الخطأ هنا -->
    <div class="error-message">
      <!-- سيتم هنا إظهار رسائل الخطأ في حال حدوث خطأ -->
      <!-- على سبيل المثال: سيتم عرض رسالة خطأ في حال كانت الحقول غير مكتملة -->
    </div>
  </form>
</div>

</body>
</html>
