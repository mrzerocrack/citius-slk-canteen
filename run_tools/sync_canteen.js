var webpost = require('./webpost');

setInterval(sync_canteen, 300000);
setInterval(sync_slp, 300000);
setInterval(sync_employee, 300000);
setInterval(sync_employee_cc, 300000);
setInterval(sync_log, 300000);

function sync_canteen(){
	const data_post = JSON.stringify({key_code:"T()tt3nh@m"});
	webpost.post(data_post, "/api/sync_canteen", function(res_post){
		console.log(res_post);
	});
}

function sync_slp(){
	const data_post = JSON.stringify({key_code:"T()tt3nh@m"});
	webpost.post(data_post, "/api/sync_slp", function(res_post){
		console.log(res_post);
	});
}

function sync_employee(){
	const data_post = JSON.stringify({key_code:"T()tt3nh@m"});
	webpost.post(data_post, "/api/sync_employee", function(res_post){
		console.log(res_post);
	});
}

function sync_employee_cc(){
	const data_post = JSON.stringify({key_code:"T()tt3nh@m"});
	webpost.post(data_post, "/api/sync_employee_cc", function(res_post){
		console.log(res_post);
	});
}

function sync_log(){
	const data_post = JSON.stringify({key_code:"T()tt3nh@m"});
	webpost.post(data_post, "/api/sync_log", function(res_post){
		console.log(res_post);
	});
}