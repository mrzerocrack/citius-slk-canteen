
var https = require('http');

module.exports = {
	post : function(dataSend, path, callback){
		const options = {
				hostname: 'localhost',
				timeout: 120000,
				port: 80,
				path: path,
				method: 'POST',
				headers: {
				'Content-Type': 'application/json; charset=utf-8',
				'Content-Length': Buffer.byteLength(dataSend)
			}
		}
		const req = https.request(options, (res) => {
			var result_data = [];
			res.on('data', function(d) {
				if (res.statusCode == '200') {
					result_data.push(d);
				}else{
					callback(null, 1);
				}
			}).on('end', function() {
				if(res.statusCode == '200'){
					var buffer = Buffer.concat(result_data);
					var final_data = buffer.toString();
					callback(final_data, 0);
				}else{
					var final_data = JSON.stringify({message:"error ! "+path+"hub admin"});
					callback(final_data, 1);
				}
			});
		});
		req.on('error', (error) => {
			console.error("error " + error);
			callback(null, 1);
		});
		req.on('timeout', function(e) {
		    req.abort();
			callback(null, 1);
		});
		req.write(dataSend);
		req.end();
		return;
	},
	get : function(path, callback){
		const options = {
				hostname: 'localhost',
				timeout: 120000,
				port: 80,
				path: path,
				method: 'GET',
		}
		const req = https.request(options, (res) => {
			var result_data = [];
			res.on('data', function(d) {
				if (res.statusCode == '200') {
					result_data.push(d);
				}else{
					callback(null, 1);
				}
			}).on('end', function() {
				if(res.statusCode == '200'){
					var buffer = Buffer.concat(result_data);
					var final_data = buffer.toString();
					callback(final_data, 0);
				}else{
					var final_data = JSON.stringify({message:"error ! "+path+"hub admin"});
					callback(final_data, 1);
				}
			});
		});
		req.on('error', (error) => {
			console.error("error " + error);
			callback(null, 1);
		});
		req.on('timeout', function(e) {
		    req.abort();
			callback(null, 1);
		});
		req.end();
		return;
	}
};
