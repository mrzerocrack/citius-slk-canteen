from time import *
import subprocess
import pexpect
import requests
import json,urllib.request
import os
import paramiko

global host_url = "http://sks.canteen/"
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

	print("sync_photo DONE")

def send_post_request(url, data):
    try:
        response = requests.post(url, data=data, verify=False, timeout=120)
        response.raise_for_status()  # Raise an exception for error HTTP status codes
        return response
    except requests.exceptions.RequestException as e:
        # Handle error and log to file
        error_message = f"[{datetime.datetime.now()}] Error: {str(e)}"
        with open("log_error.txt", "a") as f:
            f.write(error_message + "\n")
        return None

while True:
    sync_canteen = send_post_request(host_url+"api/sync_canteen", {'key_code': 'T()tt3nh@m'})
    if sync_canteen:
        print("sync_canteen DONE")
        # Lakukan sesuatu dengan respons jika berhasil
    else:
        print("sync_canteen FAILED PLEASE CEK LOG.")
        
    sync_slp = send_post_request(host_url+"api/sync_slp", {'key_code': 'T()tt3nh@m'})
    if sync_slp:
        print("sync_slp DONE")
        # Lakukan sesuatu dengan respons jika berhasil
    else:
        print("sync_slp FAILED PLEASE CEK LOG.")
        
    sync_employee = send_post_request(host_url+"api/sync_employee", {'key_code': 'T()tt3nh@m'})
    if sync_employee:
        print("sync_employee DONE")
        # Lakukan sesuatu dengan respons jika berhasil
    else:
        print("sync_employee FAILED PLEASE CEK LOG.")
        
    sync_employee_cc = send_post_request(host_url+"api/sync_employee_cc", {'key_code': 'T()tt3nh@m'})
    if sync_employee_cc:
        print("sync_employee_cc DONE")
        # Lakukan sesuatu dengan respons jika berhasil
    else:
        print("sync_employee_cc FAILED PLEASE CEK LOG.")
        
    sync_log = send_post_request(host_url+"api/sync_log", {'key_code': 'T()tt3nh@m'})
    if sync_log:
        print("sync_log DONE")
        # Lakukan sesuatu dengan respons jika berhasil
    else:
        print("sync_log FAILED PLEASE CEK LOG.")

    sync_photo()
    print("TASK DONE\nNEW TASK WILL STARTIN 60MINUTES")
    sleep(3600)
