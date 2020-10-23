function block_tqg_plugin_refresh_token(e, args) {
    e.preventDefault();

    Y.log('Enetered method block_tqg_plugin_check_user');

    var ioconfig = {
        method: 'POST',
        data: {'email' : args.email,
            'password': args.password,
            'password_confirmation': args.password},
        on: {
            success: function (o, response) {
                //OK
                var data = Y.JSON.parse(response.responseText);
                if (data.token) {
                    Y.io(M.cfg.wwwroot + '/blocks/tqg_plugin/actions/save_token.php',
                        { method: 'POST',
                          data: {'email': args.email, 'token': data.token},
                          on: {
                            success: function (o, response) {
                                alert('Login successfully!');
                                location.reload();
                            },
                            failure: function(o, response) {
                                alert('Failed to save token.');
                            }
                          }});
                }
            },
            failure: function (o, response) {
                var data = Y.JSON.parse(response.responseText);
                if(data.message) {
                    alert(data.message);
                }
                else {
                    alert(data.errors);
                }
            }
        }
    };

    Y.io('http://' + args.hostname + ':' + args.port + '/api/login', ioconfig);
}

function block_tqg_plugin_validate_token(e, args) {
    e.preventDefault();

    Y.log('Enetered method block_tqg_plugin_validate_token');

    var ioconfig = {
        method: 'GET',
        headers: {'Authorization' : 'Bearer ' + args.token},
        on: {
            success: function (o, response) {
                //OK
                var data = Y.JSON.parse(response.responseText);
                if (data) {
                    alert('Token validated.');
                }
            },
            failure: function (o, response) {
                var data = Y.JSON.parse(response.responseText);
                if(data.message) {
                    Y.io(M.cfg.wwwroot + '/blocks/tqg_plugin/actions/delete_token.php',
                        { method: 'POST',
                            data: {'email': args.email},
                            on: {
                                success: function (o, response) {
                                    alert('User does not exist');
                                    location.reload();
                                },
                                failure: function(o, response) {
                                    alert('Failed to delete token.');
                                }
                            }});
                }
                else {
                    Y.io(M.cfg.wwwroot + '/blocks/tqg_plugin/actions/delete_token.php',
                        { method: 'POST',
                            data: {'email': args.email},
                            on: {
                                success: function (o, response) {
                                    alert('User does not exist');
                                    location.reload();
                                },
                                failure: function(o, response) {
                                    alert('Failed to delete token.');
                                }
                            }});
                }
            }
        }
    };
    console.log(ioconfig);
    Y.io('http://' + args.hostname + ':' + args.port + '/api/user', ioconfig);
}

function block_tqg_plugin_update_password(e, args) {
    e.preventDefault();

    Y.log('Enetered method block_tqg_plugin_update_password');

    var ioconfig = {
        method: 'POST',
        data: {'new_password': args.password, 'new_password_confirmation': args.password},
        headers: {'Authorization' : 'Bearer ' + args.token},
        on: {
            success: function (o, response) {
                //OK
                var data = Y.JSON.parse(response.responseText);
                if (data.token) {
                    Y.io(M.cfg.wwwroot + '/blocks/tqg_plugin/actions/save_token.php',
                        { method: 'POST',
                            data: {'email': args.email, 'token': data.token},
                            on: {
                                success: function (o, response) {
                                    alert('Password changed.');
                                    location.reload();
                                },
                                failure: function(o, response) {
                                    alert('Failed to save token.');
                                }
                            }});
                }
            },
            failure: function (o, response) {
                var data = Y.JSON.parse(response.responseText);
                if(data.message) {
                    alert(data.message);
                }
                else {
                    alert(data.errors);
                }
            }
        }
    };
    console.log(ioconfig);
    Y.io('http://' + args.hostname + ':' + args.port + '/api/password', ioconfig);
}

function block_tqg_plugin_register_user(e, args) {
    e.preventDefault();

    Y.log('Enetered method block_tqg_plugin_register_user');

    var ioconfig = {
        method: 'POST',
        data: {'email' : args.email,
            'username': args.username,
            'password': args.password,
            'password_confirmation': args.password,
            'firstname': args.firstname,
            'lastname': args.lastname,
            'idnumber': args.idnumber,
            'institution': args.institution,
            'department': args.department,
            'phone1': args.phone1,
            'phone2': args.phone2,
            'city': args.city,
            'url': args.url,
            'icq': args.icq,
            'skype': args.skype,
            'aim': args.aim,
            'yahoo': args.yahoo,
            'msn': args.msn,
            'country': args.country},
        on: {
            success: function (o, response) {
                //OK
                var data = Y.JSON.parse(response.responseText);
                if (data.token) {
                    alert('Registration successfully!');
                    location.reload();
                }
            },
            failure: function (o, response) {
                var data = Y.JSON.parse(response.responseText);
                if(data.message) {
                    alert(data.message);
                }
                else {
                    alert(data.errors);
                }
            }
        }
    };

    Y.io('http://' + args.hostname + ':' + args.port + '/api/register', ioconfig);
}