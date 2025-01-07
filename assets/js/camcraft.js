let stream = null;

// אלמנטים מה-HTML
const video = document.getElementById("camera");
const canvas = document.getElementById("canvas");
const photo = document.getElementById("photo");
const captureBtn = document.getElementById("capture");
const stopCameraBtn = document.getElementById("stopCamera");
const startCameraBtn = document.getElementById("startCamera");
const downloadImgBtn = document.getElementById("downloadImg");
const saveImgBtn = document.getElementById("saveImg");
const addElementBtn = document.getElementById("addElement");

// פונקציה להפעלת המצלמה
function startCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then((mediaStream) => {
                stream = mediaStream; // שמירה של הזרם
                video.srcObject = stream;

                document.getElementById("camera-status").textContent = "המצלמה דולקת";
                document.getElementById("camera-status").style.color = "green";  // צבע ירוק להצלחה
            })
            .catch((err) => {
                document.getElementById("camera-status").textContent = "לא נתת גישה למצלמה";
                document.getElementById("camera-status").style.color = "red";  // צבע אדום לשגיאה
                console.error("Error accessing camera: ", err);
            });
    } else {
        alert("Your browser does not support camera access.");
    }
}

// הפעלת המצלמה בהתחלה
// startCamera();

// צילום ושמירה ב-canvas
captureBtn.addEventListener("click", () => {
    const context = canvas.getContext("2d");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    // המרת התמונה ל-DataURL
    const dataUrl = canvas.toDataURL("image/png");
    photo.src = dataUrl;
    photo.style.display = "block";
});

// כיבוי המצלמה
stopCameraBtn.addEventListener("click", () => {
    if (stream) {
        document.getElementById("camera-status").textContent = "המצלמה כבויה";
        document.getElementById("camera-status").style.color = "red";  // צבע אדום
        const tracks = stream.getTracks(); // קבלת כל ה"מסלולים" של הזרם
        tracks.forEach((track) => track.stop()); // עצירת כל מסלול
        video.srcObject = null; // ניקוי תצוגת הווידאו
    }
});

// הפעלת המצלמה מחדש
startCameraBtn.addEventListener("click", startCamera);

// יצירת כפתור להורדה
downloadImgBtn.addEventListener("click", () => {
    const link = document.createElement("a"); // יצירת אלמנט <a>
    link.href = canvas.toDataURL("image/png"); // הגדרת ה-Data URL כמקור
    link.download = "התמונה_שלי.png"; // שם הקובץ שיורד
    link.click(); // ביצוע ההורדה
});

// שמירה בשרת
saveImgBtn.addEventListener("click", () => {
    canvas.toBlob((blob) => {
        const formData = new FormData();
        formData.append("action", "save_camera_image");
        formData.append("image", blob, "camera_image.png");

        fetch("/wp-admin/admin-ajax.php", {
            method: "POST",
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log("התמונה נשמרה בהצלחה! URL: " + data.data.url);
                } else {
                    console.log("שגיאה בשמירת התמונה: " + data.data);
                }
            })
            .catch((error) => {
                console.error("שגיאה בשליחה לשרת:", error);
            });
    }, "image/png");
});

// // הוספת אלמנט ל-Canvas
// addElementBtn.addEventListener("click", () => {
//     const context = canvas.getContext("2d");
//     const img = new Image();
//     img.src = "/wp-content/uploads/2024/12/pL4va5OrRMqFhFqWdDNomQ-removebg-preview.png"; // תמונה לדוגמה
//     img.onload = () => {
//         context.drawImage(img, 10, 50, 50, 50); // ציור האלמנט במיקום התחלתי
//     };
// });

// הוספת אירועים לבחירת אלמנט
let selectedElement = null;
const elements = document.querySelectorAll(".selectable-element");

elements.forEach((element) => {
    element.addEventListener("click", () => {
        elements.forEach((el) => el.classList.remove("selected")); // הסרת בחירה קודמת
        element.classList.add("selected");
        selectedElement = element.src; // שמירת ה-URL של האלמנט שנבחר
        console.log("נבחר אלמנט:", selectedElement);
    });
});

// הוספת אלמנט למיקום על Canvas בלחיצה
canvas.addEventListener("click", (event) => {
    if (!selectedElement) {
        alert("יש לבחור אלמנט תחילה!");
        return;
    }

    const context = canvas.getContext("2d");
    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    const img = new Image();
    img.src = selectedElement;
    img.onload = () => {
        context.drawImage(img, x - 25, y - 25, 50, 50); // ציור האלמנט במיקום קליק
    };
});
