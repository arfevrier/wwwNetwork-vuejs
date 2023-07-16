<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#75caeb">
<meta name="msapplication-navbutton-color" content="#75caeb">
<meta name="apple-mobile-web-app-status-bar-style" content="#75caeb">
    
<title>Whois, Reverse, Nmap, Subdomain gathering</title>
<meta name="content-language" content="en">
<meta name="description" content="The main purpose of this tool is to gather in one page all the public information that can be retrieved about a domain name, IP address or AS number. The information retrieved is the sub-domains of a domain name, Whois of an IP/domain name, DNS Record, Domain reverse and IP reverse, Nmap of IP.">
<meta name="reply-to" content="me@arfevrier.fr">
    
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/3.3.7/lumen/bootstrap.min.css">
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
function print_result(txt, size, color, title, id=""){
    if(size=='petit'){size = 3;} else {size = 6;};
    
    $("#global").append('<div class="col-lg-'+size+'"><div class="panel panel-primary"><div class="panel-heading" style="background-color:'+color+';"><h3 class="panel-title">'+title+'</h3></div><div class="panel-body" id="'+id+'">'+txt+'</div></div></div>');
}
function start_info(value){
    $("#global").empty();
    if($.isNumeric(value.slice(-1))){
        $.get("https://api.arfevrier.fr/v2/host/ipv4/"+value, function(data){
            print_result('IP/AS Tested: '+value+' & IP Reverse: '+data.hostname, 'grand', '#a54141', 'INFO', 'info_div');
            var unique_id = new Date().valueOf();
            print_result('Port Opened: <span id="nmap-loading" class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>', 'grand', '#8341a5', 'Nmap (~5s)', 'nmap-body-'+unique_id);
            print_result('<span id="subdomains-loading" class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>', 'petit', '#2f9aa5', 'Subdomain Explorer (~15s)', 'subdomains-body-'+unique_id);
            nmap(value, unique_id);
            $.get("https://api.arfevrier.fr/v2/whois/"+value, function(data){
                print_result(data.content, 'petit', '#416ea5', 'Whois IP: '+data.request);
            });
            if(data.hostname!=null && data.hostname!=false){
                subdomains(data.hostname, unique_id);
                $.get("https://api.arfevrier.fr/v2/whois/"+data.hostname, function(data){
                    print_result(data.content, 'petit', '#41a57b', 'Whois Domain: '+data.request);
                });
                $.get("https://api.arfevrier.fr/v2/dig/"+data.hostname+"/"+selector_dns_value, function(data){
                    print_result(data.content, 'petit', '#9fa541', 'DNS Records: '+data.request);
                });
            }
        }).fail(function() {
            $.get("https://api.arfevrier.fr/v2/whois/"+value, function(data){
                print_result(data.content, 'petit', '#416ea5', 'Whois AS: '+data.request);
            });
        });
    } else{
       $.get("https://api.arfevrier.fr/v2/host/domain/"+value, function(data){
            print_result('Domain Tested: '+value+' & Domain Reverse: '+data.ipv4, 'grand', '#a54141', 'INFO', 'info_div');
            var unique_id = new Date().valueOf();
            print_result('Port Opened: <span id="nmap-loading" class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>', 'grand', '#8341a5', 'Nmap (~5s)', 'nmap-body-'+unique_id);
            print_result('<span id="subdomains-loading" class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>', 'petit', '#2f9aa5', 'Subdomain Explorer (~15s)', 'subdomains-body-'+unique_id);
            nmap(data.ipv4, unique_id);
            subdomains(value, unique_id);
            $.get("https://api.arfevrier.fr/v2/whois/"+value, function(data){
                print_result(data.content, 'petit', '#416ea5', 'Whois Domain: '+data.request);
            });
            $.get("https://api.arfevrier.fr/v2/dig/"+value+"/"+selector_dns_value, function(data){
                print_result(data.content, 'petit', '#9fa541', 'DNS Records: '+data.request);
            });
            if(data.ipv4!=null){
                $.get("https://api.arfevrier.fr/v2/whois/"+data.ipv4, function(data){
                    print_result(data.content, 'petit', '#41a57b', 'Whois IP: '+data.request);
                }); 
            }
        });
    };
}
function nmap(value, unique_id){
    $.get("https://api.arfevrier.fr/v2/nmap/"+value, function(data){
        if(data.ports.length > 0){
            $("#nmap-body-"+unique_id).html('Port Opened: '+data.ports.join(", "));
        }else{
            $("#nmap-body-"+unique_id).html('Port Opened: No');
        }
    });
}
function subdomains(value, unique_id){
    $.get("https://api.arfevrier.fr/v2/subdomains/"+value, function(data){
                if(data.size>0){
                    $("#subdomains-body-"+unique_id).html('% '+data.credit+'<br>%<br>% '+data.domain+'<br><br>'+data.list.join('<br>'));
                }else{
                    $("#subdomains-body-"+unique_id).html('No subdomains');
                }
    });
}
var selector_dns_value = "8.8.8.8";
function selector_dns_server(){
    $("#dns_google").toggleClass("btn-info");
    $("#dns_ovh").toggleClass("btn-info");
    if(selector_dns_value == "8.8.8.8"){selector_dns_value = "213.186.33.99";} else {selector_dns_value = "8.8.8.8";};
}
</script>
</head>
<body>
<div class="container">
<div class="bs-docs-section">
 <div class="row">
         <div class="page-header">
            <h1 id="navbar" style="display: inline-block">Whois, Nmap, Subdomain gathering</h1>
            <div class="bs-component" style="display: inline-block;margin-left:70px;top: -6px;position: relative;">
                  <input type="text" class="form-control" onkeypress="if(event.keyCode == 13){start_info($('#input_value').val());return false;}" id="input_value" placeholder="IP/Domain/AS" style="width: 150px;display: inline-block;">
                  <button class="btn btn-default" onclick="start_info($('#input_value').val());return false;" style="margin-top: -3px;">Start</button>
                  <label for="inputEmail" class="control-label" style="margin-left: 20px;margin-right: 2px;">DNS Server:</label>
                  <div class="btn-group">
                        <a id="dns_google" onclick="selector_dns_server()" class="btn btn-default btn-info">8<sup>4</sup></a>
                        <a id="dns_ovh" onclick="selector_dns_server()" class="btn btn-default">OVH</sup></a>
                 </div>
            </div>
        </div>
 </div>
</div>
</div>
<!-- Conntainer End-->
<section class="section-header" id="server-header" style="padding-left:10px;padding-right:10px;">
    <div class="row" id="global">
    </div>
</section>
</body>
</html>