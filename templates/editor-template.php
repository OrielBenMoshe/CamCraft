<div class="camera-container">
  <!-- תצוגת וידאו -->
  <video id="camera" autoplay playsinline></video>
  
  <!-- Canvas נסתר -->
  <canvas id="canvas" style="display: none;"></canvas>
  
  <!-- תצוגת תמונה שצולמה -->
  <img id="photo" alt="Captured Image" />

  <!-- כפתורי שליטה -->
  <div class="camera-buttons">
    <div id="camera-status"></div>
    <button id="capture">צלם</button>
    <button id="stopCamera">כיבוי מצלמה</button>
    <button id="startCamera">הדלקת מצלמה</button>
    <button id="downloadImg">הורד תמונה</button>
    <button id="saveImg">שמור בשרת</button>
    <button id="addElement">הוסף אלמנט</button>
  </div>

  <!-- בחירת אלמנטים -->
  <div class="element-selector">
    <h3>בחר אלמנט:</h3>
    <div id="elements">
      <img src="/wp-content/uploads/2024/12/Kul0t8lpTFuCBkS7bFCQhQ.jpg" class="selectable-element" alt="Element 1" />
      <img src="/wp-content/uploads/2024/12/Kul0t8lpTFuCBkS7bFCQhQ-removebg-preview.png" class="selectable-element" alt="Element 2" />
      <img src="/wp-content/uploads/2024/12/pL4va5OrRMqFhFqWdDNomQ-removebg-preview.png" class="selectable-element" alt="Element 3" />
    </div>
  </div>
</div>
