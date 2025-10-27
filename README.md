# 🧩 PHP ZIP Part Joiner

A simple PHP script that **joins multiple ZIP parts** (e.g., `.zip.001`, `.zip.002`, ...) into a single file and **automatically extracts** it on your server.

Perfect for hosting environments like **cPanel** or **InfinityFree**, where the upload size limit is small (e.g., 10 MB).  
This tool allows you to upload large ZIP files in parts and merge them directly on the server.

---

## 🚀 Features

✅ Merge split ZIP parts (e.g., `myfile.zip.001`, `myfile.zip.002`, `myfile.zip.003`, …)  
✅ Automatically unzip the merged file  
✅ Works on shared hosting (like cPanel, InfinityFree, etc.)  
✅ Simple web-based interface  
✅ Optional base-name auto-selection  
✅ Secure access via a secret key  
✅ Clean, responsive Persian interface  

---

## 📦 How to Use

1. **Split your large ZIP file** on your computer using a tool such as **7-Zip** or **WinRAR**:  
   ```bash
   7z a -v10m myfile.zip mybigfolder/

This will create parts like:

myfile.zip.001
myfile.zip.002
myfile.zip.003
...

    Upload all parts (.zip.001, .zip.002, etc.) to the same folder on your hosting.

    Upload the script join_and_unzip.php into the same folder.

    Edit the script and set your own secret access key at the top:

$ACCESS_KEY = 'Change_This_To_A_Strong_Key';

Open the script in your browser:

    https://yourdomain.com/path/join_and_unzip.php?key=YOUR_KEY

    Select your files manually or use the base name to auto-select them.

    Click “ادغام و استخراج” (Join and Unzip)
    → The files will be merged into a single ZIP and automatically extracted!

⚙️ Configuration

You can modify these options in the script:

$DELETE_PARTS_AFTER_JOIN = false; // Delete original parts after merging
$UNZIP_AFTER_JOIN = true;         // Automatically unzip if the merged file is a ZIP
$UNZIP_DESTINATION = __DIR__;     // Destination folder for extraction
$CHUNK_SIZE = 8 * 1024 * 1024;    // Read/write chunk size (8 MB)

🛠 Requirements

    PHP 7.4 or higher

    PHP ZipArchive extension enabled

    Works perfectly on cPanel, InfinityFree, and most shared hosts

🧰 Example Use Case

If your hosting has a 10 MB upload limit, and your file is 100 MB:

    Split the file locally:

    7z a -v10m myfile.zip myfolder/

    Upload all parts to your hosting.

    Open join_and_unzip.php in your browser.

    Merge them and extract automatically. 🎉

💡 Tips

    Always use a strong $ACCESS_KEY to prevent unauthorized access.

    If extraction doesn’t work, make sure the ZipArchive extension is enabled in your PHP environment.

    You can safely delete this script from your server after use for better security.

🌟 Star this repository if you find it useful!


