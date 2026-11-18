import os
import zipfile
import ftplib
import sys

def zipdir(path, ziph):
    for root, dirs, files in os.walk(path):
        if 'node_modules' in dirs:
            dirs.remove('node_modules')
        if '.git' in dirs:
            dirs.remove('.git')
        
        for file in files:
            if file == '.env.testing':
                continue
            
            file_path = os.path.join(root, file)
            # Add file to zip archive
            ziph.write(file_path, os.path.relpath(file_path, path))

print("Zipping crm...")
with zipfile.ZipFile('crm_full.zip', 'w', zipfile.ZIP_DEFLATED) as zipf:
    zipdir('crm', zipf)
print("Zipped successfully!")

ftp_host = "ftp.thegreen.homes"
ftp_user = "u495244335crm"
ftp_pass = "GreenHomesCRM@2026!"
remote_dir = "public_html/crm.thegreen.homes"

print(f"Connecting to {ftp_host}...")
try:
    ftp = ftplib.FTP(ftp_host)
    ftp.login(ftp_user, ftp_pass)
    print("Logged in successfully.")
    
    # Try changing to the directory, if it exists
    try:
        ftp.cwd(remote_dir)
        print(f"Changed to {remote_dir}")
    except Exception as e:
        print(f"Could not cwd to {remote_dir}. Trying root or creating dirs... {e}")
        # The user's FTP account might be chrooted to the directory directly!
        # If the FTP user was created FOR public_html/crm.thegreen.homes, its root (/) IS that directory.
        try:
            ftp.cwd("/")
            print("Changed to /")
        except Exception as e2:
            print("Failed to cwd /")
            
    # Upload crm_full.zip
    print("Uploading crm_full.zip...")
    with open('crm_full.zip', 'rb') as f:
        ftp.storbinary('STOR crm.zip', f)
    print("crm.zip uploaded.")
    
    # Upload unzip.php
    print("Uploading unzip.php...")
    with open('unzip.php', 'rb') as f:
        ftp.storbinary('STOR unzip.php', f)
    print("unzip.php uploaded.")
    
    ftp.quit()
    print("All done!")
except Exception as e:
    print(f"FTP Error: {e}")
    sys.exit(1)
