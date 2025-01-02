import tkinter as tk
import subprocess
import threading
import socket
from time import *
import requests
import json,urllib.request
import os
import paramiko
from datetime import datetime

reverb_pid = None
queue_pid = None
url_api = "http://192.168.100.199"
#subprocess.Popen(['php', '../artisan', 'reverb:start'])
#subprocess.Popen(['php', '../artisan', 'queue:work'])

def check_port_in_use(port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        try:
            s.bind(('0.0.0.0', port))
            return False
        except OSError:
            return True

def start_reverb():
    global reverb_pid
    global queue_pid
    try:
        process_reverb = subprocess.Popen(['php', '../artisan', 'reverb:start'])
        reverb_pid = process_reverb.pid
        add_text(f"Reverb started with PID: {reverb_pid}")
        process_queue = subprocess.Popen(['php', '../artisan', 'queue:work'])
        queue_pid = process_queue.pid
        add_text(f"[{datetime.now()}] Queue started with PID: {queue_pid}")
    except subprocess.CalledProcessError as e:
        add_text(f"[{datetime.now()}] Error starting Reverb: {str(e)}")

def stop_reverb():
    global reverb_pid
    global queue_pid
    try:
        if reverb_pid:
            subprocess.call(['taskkill', '/PID', str(reverb_pid), '/F'])
            reverb_pid = None
            add_text(f"[{datetime.now()}] Reverb stopped")
        else:
            add_text(f"[{datetime.now()}] Reverb is not running")
    except subprocess.CalledProcessError as e:
        add_text(f"[{datetime.now()}] Error stopping Reverb: {str(e)}")
    try:
        if queue_pid:
            subprocess.call(['taskkill', '/PID', str(queue_pid), '/F'])
            queue_pid = None
            add_text(f"[{datetime.now()}] Queue stopped")
        else:
            add_text(f"[{datetime.now()}] Queue is not running")
    except subprocess.CalledProcessError as e:
        add_text(f"[{datetime.now()}] Error stopping Reverb: {str(e)}")

# ... sisanya sama seperti kode sebelumnya
        
def check_reverb_status():
    while True:
        if check_port_in_use(8080):
            start_button.config(state=tk.DISABLED)
            stop_button.config(state=tk.NORMAL)
            status_label.config(text="Server is running")
        else:
            start_button.config(state=tk.NORMAL)
            stop_button.config(state=tk.DISABLED)
            status_label.config(text="Server is not running, please click start to start the server")
        sleep(2)  # Periksa setiap 2 detik

def sync_photo():
	ssh = paramiko.SSHClient()
	ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
	ssh.connect('103.146.203.202', username="root", password="!Zionis123")
	sftp = ssh.open_sftp()
	localpath = '../public/assets/images/employee/'
	remotepath = '/var/www/citius-slk/public/assets/images/employee/'
	list_file = sftp.listdir(remotepath)
	for file in list_file:
		sftp.get(remotepath+file, localpath+file)
	sftp.close()
	ssh.close()

	add_text(f"[{datetime.now()}] sync_photo DONE")

def send_post_request(url, data):
    try:
        response = requests.post(url, data=data, verify=False, timeout=120)
        response.raise_for_status()  # Raise an exception for error HTTP status codes
        return response
    except requests.exceptions.RequestException as e:
        # Handle error and log to file
        error_message = f"[{datetime.now()}] Error: {str(e)}"
        with open("log_error.txt", "a") as f:
            f.write(error_message + "\n")
        return None
    
def add_text(text):
    text_area.configure(state=tk.NORMAL)  # Enable editing sementara
    text_area.insert(tk.END, text + "\n")
    text_area.configure(state=tk.DISABLED)  # Disable editing kembali
    text_area.see(tk.END)  # Scroll ke bawah secara otomatis
    
def synchronizer():
    while True:
        sync_canteen = send_post_request(url_api+"/api/sync_canteen", {'key_code': 'T()tt3nh@m'})
        if sync_canteen:
            add_text(f"[{datetime.now()}] sync_canteen DONE")
            # Lakukan sesuatu dengan respons jika berhasil
        else:
            add_text(f"[{datetime.now()}] sync_canteen FAILED PLEASE CEK LOG.")
            
        sync_slp = send_post_request(url_api+"/api/sync_slp", {'key_code': 'T()tt3nh@m'})
        if sync_slp:
            add_text(f"[{datetime.now()}] sync_slp DONE")
            # Lakukan sesuatu dengan respons jika berhasil
        else:
            add_text(f"[{datetime.now()}] sync_slp FAILED PLEASE CEK LOG.")
            
        sync_employee = send_post_request(url_api+"/api/sync_employee", {'key_code': 'T()tt3nh@m'})
        if sync_employee:
            add_text(f"[{datetime.now()}] sync_employee DONE")
            # Lakukan sesuatu dengan respons jika berhasil
        else:
            add_text(f"[{datetime.now()}] sync_employee FAILED PLEASE CEK LOG.")
            
        sync_employee_cc = send_post_request(url_api+"/api/sync_employee_cc", {'key_code': 'T()tt3nh@m'})
        if sync_employee_cc:
            add_text(f"[{datetime.now()}] sync_employee_cc DONE")
            # Lakukan sesuatu dengan respons jika berhasil
        else:
            add_text(f"[{datetime.now()}] sync_employee_cc FAILED PLEASE CEK LOG.")
            
        sync_log = send_post_request(url_api+"/api/sync_log", {'key_code': 'T()tt3nh@m'})
        if sync_log:
            add_text(f"[{datetime.now()}] sync_log DONE")
            # Lakukan sesuatu dengan respons jika berhasil
        else:
            add_text(f"[{datetime.now()}] sync_log FAILED PLEASE CEK LOG.")

        sync_photo()
        add_text(f"[{datetime.now()}] TASK DONE\nNEW TASK WILL STARTIN 2 MINUTES")
        sleep(120)

# Buat jendela utama
window = tk.Tk()
window.title("Server Controller")
window.geometry("800x600")

# Buat tombol start
start_button = tk.Button(window, text="Start Server", command=start_reverb)
start_button.pack()

# Buat tombol stop
stop_button = tk.Button(window, text="Stop Server", command=stop_reverb)
stop_button.pack()

# Buat text area dengan scrollbar
text_area = tk.Text(window, height=10)
text_area.configure(state=tk.DISABLED)
text_area.pack(fill="both", expand=True)

# Buat label untuk menampilkan status
server_label = tk.Label(window, text="")
server_label.pack()
status_label = tk.Label(window, text="Server status")
status_label.pack()

# Mulai thread untuk memeriksa status Reverb
status_thread = threading.Thread(target=check_reverb_status)
status_thread.daemon = True  # Agar thread mati ketika program utama selesai
status_thread.start()

# Mulai thread untuk memeriksa status Reverb
sync_thread = threading.Thread(target=synchronizer)
sync_thread.daemon = True  # Agar thread mati ketika program utama selesai
sync_thread.start()

# Jalankan GUI
window.mainloop()