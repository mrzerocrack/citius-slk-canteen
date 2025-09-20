<!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="{{URL::to('jamu/bootstrap.min.css')}}" rel="stylesheet">
  <title></title>
  <script src="{{URL::to('jamu/jquery.js')}}"></script>
  <script src="{{URL::to('jamu/bootstrap.bundle.min.js')}}"></script>
</head>

<style>
  #log_text {
    position: absolute;
    bottom: 5px;
    z-index: 9999;
    background: #000000b5;
    padding: 15px;
    border: 1px solid black;
    border-radius: 20px;
    left: 5px;
  }

  #log_text>p {
    color: #ffffff;
    padding: 0px !important;
    margin: 0px !important;
    font-weight: bolder;
    text-shadow: 0px -2px 3px #000000;
  }

  .header-last-trx {
    font-size: 20px;
  }
</style>

<body>
  <div class="w-100" id="main-content" style="">
    <div id="log_text">
      <div class="text-center text-alert text-light fw-bold mb-2 header-last-trx">
        Last 5 Transactions
      </div>
    </div>
    <img src="{{URL::to('jamu/bg-top.png')}}" class="w-100" style="" id="bg-top">
    <div class="row">
      <div class="col-12 col-sm-4">
        <img src="{{URL::to('jamu/bg-photo-back.png')}}" id="bg-photo-back"
          style="position: absolute;z-index: 2;left: 30px;margin-top: 30px;max-width: 300px;">
        <div id="photo-employee"
          style="overflow:hidden;z-index:3;position: absolute;margin-top: 30px;;left: 30px;text-align: center;align-content: end;">
          <img id="photo-employee-img" src="" style="width:60%;margin-bottom:5px">
        </div>
        <img src="" id="trx-notif" style="position: absolute;z-index: 4;right: 30px;">
        <img src="{{URL::to('jamu/bg-photo-front.png')}}" id="bg-photo-front"
          style="position: absolute;z-index: 4;left: 30px;margin-top: 30px;max-width: 300px;">
      </div>
      <div class="col-12 col-sm-8">
        <div id="employee-name"
          style="background-color: #a9151f;width: max-content;padding: 20px;border-radius: 25px;color: white;font-family: system-ui;font-weight: 700;font-size: 30px;">
          EMPLOYEE NAME</div>
        <div style="font-size: 35px;font-weight: 900;font-family: system-ui;color: #a9151f;margin-left: 35px;"
          id="employee-card-id"> ID : 123456</div>
        <div style="margin-left: 35px;">
          <div class="d-flex">
            <div style="font-size: 30px;font-weight: 700;font-family: system-ui;">Status :&nbsp;</div>
            <div style="font-size: 30px;font-weight: 700;font-family: system-ui;" id="status-text">Please tap your card
              ...</div>
          </div>
        </div>
      </div>
    </div>
    <img id="bg-bottom" src="{{URL::to('jamu/bg-bottom.png')}}" class="w-100"
      style="position: absolute;bottom: 0;left: 0;">
  </div>
  <div style="position: absolute;margin-left: auto;margin-right: auto;left: 0;right: 0;text-align: center;top: 0;"
    id="status" class="text-danger">Not Connected</div>
  <div class="container" id="input_group">
    <div class="row">
      <div class="col-12 col-md-6 mt-5">
        <div class="input-group mb-3">
          <select name="canteen_name" id="canteen_name_inp" class="form-control">
            <option value="1">Canteen 1</option>
            <option value="2">Canteen 2</option>
            <option value="3">Canteen 3</option>
          </select>
          <button class="btn btn-outline-secondary" type="button" id="join">Connect</button>
        </div>
      </div>
      <div class="col-12 align-middle d-flex align-content-center flex-wrap" id="content_div">
        <img src="" id="photo_pic" style="width: auto;margin: auto;">
        <h1 id="text_content" class="text-center w-100"></h1>
      </div>
    </div>
  </div>
  <script src="{{URL::to('jamu/pusher.min.js')}}"></script>
  <script>
    var canteen_name = "";
    var pusher = new Pusher("cmswg0wixc8pjbapuzvf", {
      cluster: "",
      enabledTransports: ['ws'],
      forceTLS: false,
      wsHost: "{{env("APP_DOMAIN")}}",
      wsPort: "8080"
    });
    var channel = null;
    var interval_show_data = null;
    $(document).ready(function () {
      $("#content_div").css("minHeight", $(document).height());
      $("#photo_pic").css("maxHeight", ($(document).height() / 2));
      $("#main-content").css("height", $(window).height());
      $("#bg-photo-back").css("top", $("#bg-top").height());
      $("#bg-photo-front").css("top", $("#bg-top").height());
      $("#trx-notif").css("bottom", $("#bg-bottom").height());
      $("#photo-employee").css("width", $("#bg-photo-front").width());
      $("#photo-employee").css("height", $("#bg-photo-front").height());
      $(window).resize(function () {
        $("#content_div").css("minHeight", $(document).height());
        $("#photo_pic").css("maxHeight", ($(document).height() / 2));
        $("#main-content").css("height", $(window).height());
        $("#bg-photo-back").css("top", $("#bg-top").height());
        $("#bg-photo-front").css("top", $("#bg-top").height());
        $("#trx-notif").css("bottom", $("#bg-bottom").height());
        $("#photo-employee").css("width", $("#bg-photo-front").width());
        $("#photo-employee").css("height", $("#bg-photo-front").height());

      });
      $("#photo-employee").css("display", "none");
      $("#employee-name").css("display", "none");
      $("#employee-card-id").css("display", "none");
      $("#main-content").css("display", "none");
    });

    $("#join").click(function () {
      canteen_name = $("#canteen_name_inp").val();
      $("#status").addClass("text-success");
      $("#status").removeClass("text-danger");
      $("#status").html("canteen " + canteen_name);
      $("#input_group").remove();
      $("#main-content").css("display", "block");
      get_last_trx();
      channel = pusher.subscribe("canteen-tapping");
      channel.bind("App\\Events\\CardTapBroadcastEvent", (data) => {
        try {
          console.log(data);
          if (data.canteen_name == canteen_name) {
            $("#status-text").html(data.msg.replaceAll("\n", "<br>"));
            $("#employee-name").html(data.employee_name);
            $("#employee-card-id").html("Card : " + data.idcard);
            if (data.hasOwnProperty('photo')) {
              $("#photo-employee-img").prop("src", "{{URL::to('')}}/" + data.photo);
              $("#photo-employee").css("display", "block");
            }
            $("#employee-name").css("display", "block");
            $("#employee-card-id").css("display", "block");
            clearInterval(interval_show_data)
            interval_show_data = setTimeout(function () {
              $("#status-text").html("Please tap your card ...");
              $("#photo-employee").css("display", "none");
              $("#photo-employee").prop("src", "");
              $("#trx-notif").prop("src", "");
              $("#employee-name").css("display", "none");
              $("#employee-card-id").css("display", "none");
            }, 60000);
            if (data.status == 0) {
              var audio = new Audio('{{URL::to("jamu/gagal.wav")}}');
              $("#trx-notif").prop("src", '{{URL::to("jamu/trx-fail.png")}}');
            } else if (data.status == 1) {
              $("#trx-notif").prop("src", '{{URL::to("jamu/trx-success.png")}}');
              var audio = new Audio('{{URL::to("jamu/berhasil.wav")}}');
            }
            audio.play();
          }
          get_last_trx();
        } catch (e) {
          console.log(e);
        }
      });
    });
    function numberWithCommas(x) {
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function get_last_trx() {
      $.ajax({
        url: window.location.protocol + '//' + window.location.host + '/api/get_last_5_trx', // Ganti dengan URL API Anda
        method: 'POST',
        data: { canteen_name: canteen_name },
        success: function (response) {
          // Bersihkan konten sebelumnya
          //$('#log_text').empty();
          $('#log_text > p').remove();
          var json_response = JSON.parse(response)

          // Loop melalui array JSON dan buat elemen <p>
          for (var i = 0; i < json_response.length; i++) {
            $('#log_text').append('<p>' + json_response[i] + '</p>');
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error('Error:', textStatus, errorThrown);
        }
      });
    }

  </script>
</body>

</html>