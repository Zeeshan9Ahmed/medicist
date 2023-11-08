const express = require("express");
const app = express();
var fs = require("fs");
// const options = {
//     key: fs.readFileSync('/home/findnseekjobs/ssl/keys/b5787_a7e5f_b85026db5197cf272e15ac0b82ff2539.key'),
//     cert: fs.readFileSync('/home/findnseekjobs/ssl/certs/www_findnseekjobs_com_b5787_a7e5f_1684967689_6c6ccf47beeb3b5c57b10e5668e5e298.crt'),
// };
// const server = require('https').createServer(options, app);
const server = require("http").createServer(app);
var io = require("socket.io")(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST", "PATCH", "DELETE"],
        credentials: true,
        transports: ["websocket", "polling"],
        allowEIO3: false,
    },
});
var mysql = require("mysql");
var con_mysql = mysql.createPool({
    host: "localhost",
    user: "root",
    password: "",
    database: "medicist",
    debug: true,
    charset: "utf8mb4",
});

var FCM = require("fcm-node");
var serverKey =
    "AAAAs94xrWo:APA91bHvckSm-23tuJMGbB4C9bx6wGDQJLMi6vC4w_W0RtWIHMTeQCwcFuke0giw5RWT9QeHSmybGmF9ug8rZ-GbpR9JjQz4nQO0DGcJCGJ2YjFbTsHS6DfLcl3-edFop04H70cJ0Y6A"; //put your server key here
var fcm = new FCM(serverKey);

// SOCKET START
io.on("connection", function (socket) {
    console.log("user connected. ", socket.connected);

    // GET MESSAGES EMIT
    socket.on("get_messages", function (object) {
        console.log("GET_MESG", object.sender_id);
        var user_room = "user_" + object.sender_id;
        socket.join(user_room);

        get_messages(object, function (response) {
            if (response) {
                console.log("get_messages has been successfully executed...");
                io.to(user_room).emit("response", {
                    object_type: "get_messages",
                    data: response,
                });
            } else {
                console.log("get_messages has been failed...");
                io.to(user_room).emit("error", {
                    object_type: "get_messages",
                    message: "There is some problem in get_messages...",
                });
            }
        });
    });

    //GET GROUP MESSAGE
    socket.on("group_get_messages", function (object) {
        console.log(object);

        var group_room = "group_" + object.group_id;
        var sender = "user_" + object.sender_id;
        socket.join(group_room);
        socket.join(sender);
        group_get_messages(object, function (response) {
            if (response) {
                console.log("get_messages has been successfully executed...");
                io.to(sender).emit("response", {
                    object_type: "get_messages",
                    data: response,
                });
            } else {
                console.log("get_messages has been failed...");
                io.to(group_room).emit("error", {
                    object_type: "get_messages",
                    message: "There is some problem in get_messages...",
                });
            }
        });
    });

    // SEND MESSAGE EMIT
    socket.on("send_message", function (object) {
        var sender_room = "user_" + object.sender_id;
        var receiver_room = "user_" + object.reciever_id;
        console.log("trting to send mesg", object);
        send_message(object, function (response) {
            if (response) {
                if (response[0]["user_device_token"] == null) {
                    io.to(sender_room).to(receiver_room).emit("response", {
                        object_type: "get_message",
                        data: response[0],
                    });
                    console.log("Successfully sent with response: ");
                } else {
                    var message = {
                        //this may vary according to the message type (single recipient, multicast, topic, et cetera)
                        to: response[0]["user_device_token"],
                        collapse_key: "your_collapse_key",

                        notification: {
                            title: "Chat Notification",
                            body:
                                response[0]["full_name"] +
                                " Send you a message",
                            // user_name: response[0]['full_name'],
                            notification_type: "chat",
                            redirection_id: object.sender_id,
                            vibrate: 1,
                            sound: 1,
                        },

                        data: {
                            //you can send only notification or only data(or include both)
                            title: "Chat Notification",
                            body:
                                response[0]["full_name"] +
                                " Send you a message",
                            //user_name: response[0]['user_name'],
                            notification_type: "CHAT",
                            redirection_id: object.sender_id,
                            vibrate: 1,
                            sound: 1,
                        },
                    };

                    fcm.send(message, function (err, response_two) {
                        if (err) {
                            console.log("Something has gone wrong!");
                            io.to(sender_room)
                                .to(receiver_room)
                                .emit("response", {
                                    object_type: "get_message",
                                    data: response[0],
                                });
                        } else {
                            // console.log("send_message has been successfully executed...");
                            io.to(sender_room)
                                .to(receiver_room)
                                .emit("response", {
                                    object_type: "get_message",
                                    data: response[0],
                                });
                            console.log(response[0]);
                            // console.log("Successfully sent with response: ", response_two);
                        }
                    });
                }
            } else {
                console.log("send_message has been failed...");
                io.to(sender_room).to(receiver_room).emit("error", {
                    object_type: "get_message",
                    message: "There is some problem in get_message...",
                });
            }
        });
    });

    //SEND GROUP MESSAGE
    socket.on("group_send_message", function (object) {
        var group_room = "group_" + object.group_id;
        socket.join(group_room);
        group_send_message(object, function (response) {
            if (response) {
                console.log("send_message has been successfully executed...");
                io.to(group_room).emit("response", {
                    object_type: "get_message",
                    data: response[0],
                });
            } else {
                console.log("send_message has been failed...");
                io.to(group_room).emit("error", {
                    object_type: "get_message",
                    message: "There is some problem in get_messages...",
                });
            }
        });
    });
    // DELETE MESSAGE EMIT
    socket.on("delete_message", function (object) {
        var chat_id = object.chat_id;
        var sender_room = "user_" + object.sender_id;
        var receiver_room = "user_" + object.reciever_id;
        delete_message(object, function (response) {
            io.to(sender_room).to(receiver_room).emit("response", {
                object_type: "delete_message",
                data: chat_id,
            });
        });
    });

    socket.on("disconnect", function () {
        console.log("Use disconnection", socket.id);
    });
});
// SOCKET END


// GET MESSAGES FUNCTION
var get_messages = function (object, callback) {
    con_mysql.getConnection(function (error, connection) {
        if (error) {
            callback(false);
        } else {
            connection.query(
                `select 
            Case when users.role = 'company' 
            THEN 
            (SELECT companies.representative_name FROM companies WHERE companies.user_id = chats.chat_sender_id ORDER by companies.id asc LIMIT 1) 
            ELSE users.full_name END as 
            full_name,
            users.avatar,
            users.role,
            chats.chat_id, 
            chats.chat_sender_id,
            chats.chat_reciever_id, 
            chats.chat_group_id,
            chats.chat_message,
            chats.chat_type,
            chats.created_at
            from chats 
            
            inner join users on chats.chat_sender_id = users.id
            WHERE (chats.chat_sender_id = ${object.sender_id} 
            AND chats.chat_reciever_id=${object.reciever_id}) 
            OR (chats.chat_sender_id=${object.reciever_id} 
            AND chats.chat_reciever_id=${object.sender_id}) 
            
            order by chats.chat_id ASC`,
                function (error, data) {
                    connection.release();
                    if (error) {
                        callback(false);
                    } else {
                        callback(data);
                    }
                }
            );
        }
    });
};

//GROUP MESSAGE
var group_get_messages = function (object, callback) {
    con_mysql.getConnection(function (error, connection) {
        if (error) {
            callback(false);
        } else {
            connection.query(
                `select 
            users.first_name,
            users.last_name, 
            chats.chat_id, 
            chats.chat_sender_id,
            chats.chat_reciever_id, 
            chats.chat_group_id,
            chats.chat_group_type,
            chats.chat_message,
            chats.chat_type,
            chats.created_at
            from chats 
            inner join users on chats.chat_sender_id = users.id
            WHERE chats.chat_group_id=${object.group_id} AND chats.chat_group_type="${object.group_type}" order by chats.chat_id ASC`,
                function (error, data) {
                    connection.release();
                    if (error) {
                        callback(false);
                    } else {
                        callback(data);
                    }
                }
            );
        }
    });
};

// SEND MESSAGE FUNCTION
var send_message = function (object, callback) {
    console.log("Send msf call bacj");
    con_mysql.getConnection(function (error, connection) {
        if (error) {
            console.log("CONNECTIOn ERROR ON SEND MESSAFE");
            callback(false);
        } else {
            var new_message = mysql_real_escape_string(object.message);
            connection.query(
                `INSERT INTO chats (chat_sender_id , chat_reciever_id , chat_message, chat_type,chat_group_type,created_at,updated_at) VALUES ('${object.sender_id}' , '${object.reciever_id}', '${new_message}', '${object.chat_type}','${object.group_type}',NOW(),NOW())`,
                function (error, data) {
                    if (error) {
                        console.log("FAILED TO VERIFY LIST");
                        callback(false);
                    } else {
                        console.log(
                            "update_list has been successfully executed..."
                        );
                        connection.query(
                            `SELECT 
                                u.first_name,
                                u.last_name, 
                                u.avatar, 
                                c.chat_id, 
                                c.chat_sender_id,
                                c.chat_reciever_id, 
                                c.chat_group_id,
                                c.chat_message,
                                c.chat_type,
                                c.created_at
                                FROM users AS u
                                JOIN chats AS c
                                ON u.id = c.chat_sender_id
                                WHERE c.chat_id = '${data.insertId}'`,
                            function (error, data) {
                                connection.release();
                                if (error) {
                                    callback(false);
                                } else {
                                    callback(data);
                                }
                            }
                        );
                    }
                }
            );
        }
    });
};

//SEND GROUP MESSAGE
var group_send_message = function (object, callback) {
    console.log("Send msf call bacj");
    con_mysql.getConnection(function (error, connection) {
        if (error) {
            console.log("CONNECTIOn ERROR ON SEND MESSAFE");
            callback(false);
        } else {
            var new_message = mysql_real_escape_string(object.message);
            connection.query(
                `  INSERT INTO chats (chat_sender_id , chat_group_id , chat_message,chat_group_type,created_at) VALUES ('${object.sender_id}' , '${object.group_id}', '${new_message}','${object.group_type}',NOW())`,
                function (error, data) {
                    if (error) {
                        console.log("FAILED TO VERIFY LIST");
                        callback(false);
                    } else {
                        console.log(
                            "update_list has been successfully executed..."
                        );
                        connection.query(
                            `SELECT 
                                u.first_name,
                                u.last_name, 
                                u.avatar, 
                                c.chat_id, 
                                c.chat_sender_id,
                                c.chat_reciever_id, 
                                c.chat_group_id,
                                c.chat_message,
                                c.chat_type,
                                c.created_at
                                FROM users AS u
                                JOIN chats AS c
                                ON u.id = c.chat_sender_id
                                WHERE c.chat_id = '${data.insertId}'`,
                            function (error, data) {
                                connection.release();
                                if (error) {
                                    callback(false);
                                } else {
                                    callback(data);
                                }
                            }
                        );
                    }
                }
            );
        }
    });
};
// DELETE MESSAGE FUNCTION
var delete_message = function (object, callback) {
    con_mysql.getConnection(function (error, connection) {
        if (error) {
            console.log("CONNECTIOn ERROR ON SEND MESSAFE");
            callback(false);
        } else {
            connection.query(
                `delete from chats where chat_id = '${object.chat_id}'`,
                function (error, data) {
                    if (error) {
                        console.log("FAILED TO VERIFY LIST");
                        callback(false);
                    } else {
                        callback(true);
                    }
                }
            );
        }
    });
};

function mysql_real_escape_string(str) {
    return str.replace(/[\0\x08\x09\x1a\n\r"'\\\%]/g, function (char) {
        switch (char) {
            case "\0":
                return "\\0";
            case "\x08":
                return "\\b";
            case "\x09":
                return "\\t";
            case "\x1a":
                return "\\z";
            case "\n":
                return "\\n";
            case "\r":
                return "\\r";
            case '"':
            case "'":
            case "\\":
            case "%":
                return "\\" + char; // prepends a backslash to backslash, percent,
            // and double/single quotes
            default:
                return char;
        }
    });
}

// SERVER LISTENER
server.listen(3000, function () {
    console.log("Server is running on port 3000");
});
