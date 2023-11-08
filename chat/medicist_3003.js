const express = require('express');
const app = express();
var fs = require('fs');
const Agora = require("agora-access-token");

var serviceAccount = require("./firebase-key.json");
const { body, validationResult } = require('express-validator');
 const appID = "7426f9695fb44146955c6087a7817f85";
 const appCertificate = "8ad07c8eb5f44c7fa43962b78b5c1344";
 const expirationTimeInSeconds = 3600;
const { Sequelize } = require('sequelize');


const options = {
    key: fs.readFileSync('/home/server1appsstagi/ssl/keys/be60c_d6c69_8e905ddd3bb9d4c5ece4028b9dbb5ff4.key'),
    cert: fs.readFileSync('/home/server1appsstagi/ssl/certs/server1_appsstaging_com_be60c_d6c69_1695868624_7cd578f3f6dcc5667aca7d82c5f047de.crt'),
};
const server = require('https').createServer(options, app);
// const server = require('http').createServer( app);
var io = require('socket.io')(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST","PATCH","DELETE"],
        credentials: true,
        transports: ['websocket', 'polling'],
        allowEIO3: false
    },
});
var mysql = require("mysql");
var con_mysql = mysql.createPool({
    host: "localhost",
    user: "server1appsstagi_medicist_user",
    password: "0WDC._4dYdQR",
    database: "server1appsstagi_medicist_db",
    debug: true,
    charset:'utf8mb4'
});


const sequelize = new Sequelize('server1appsstagi_medicist_db', 'server1appsstagi_medicist_user', '0WDC._4dYdQR', {
    host: 'localhost',
    dialect: 'mysql' 
  });

  const User = sequelize.define('User', {
    first_name: Sequelize.STRING,
    last_name: Sequelize.STRING,
    email: Sequelize.STRING,
    device_token: Sequelize.STRING,
    created_at: Sequelize.STRING,
  }, {
    tableName: 'users',
    timestamps: false, // Disable timestamps
  });
var FCM = require('fcm-node');
var serverKey = 'AAAAQn8vSX4:APA91bETrBTfRFu7obreUQ89FnRhMwXvHX2q_EmQBFlEsU3PtL-wvWQYbKWDmDedhVKgeNFPKUbLgc0qUkkklXyuVNJ-PXY8JKjH9E4twnlVYodWczocT6PviJNh1_2A2PhbwYCMowyW'; //put your server key here
var fcm = new FCM(serverKey);
const notification_options = {
    priority: "high",
    timeToLive: 60 * 60 * 24
  };


app.use(express.json());


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
                    io.to(sender_room)
                        .to(receiver_room)
                        .emit("response", {
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
                io.to(sender_room)
                    .to(receiver_room)
                    .emit("error", {
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
            io.to(sender_room)
                .to(receiver_room)
                .emit("response", {
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





async function sendFCMNotification(
    deviceToken,
    type,
    senderName,
    senderId,
    receiverId,
    channel,
    senderToken,
    receiverToken,
    image,
    callback
) {
    let sound = "";

    const messageNotification = {
        to: deviceToken,
        collapse_key: "your_collapse_key",
        notification: {
            title: "Medicist",
            body: `${senderName} is Calling you`,
            notification_type: type,
            image: image,
            sound: sound, // Use the appropriate sound based on the device type
        },
        data: {
            title: `${senderName} is Calling you`,
            notification_type: type,
            to_user_id: receiverId,
            from_user_id: senderId,
            channel_name: channel,
            sender_token: senderToken,
            receiver_token: receiverToken,
            image: image,
            sound: sound, // Use the appropriate sound based on the device type
        },
    };

    // Send the FCM notification
    fcm.send(messageNotification, callback);
}


app.post(
    '/rtctoken',
    [
        body('sender_id').notEmpty().withMessage('sender_id field is required'),
        body('receiver_id').notEmpty().withMessage('receiver_id field is required'),
    ],
   async (req, res) => {
    try {
        const errors = validationResult(req);
        if (!errors.isEmpty()) {
          return res.status(400).json({ status: 0,message: errors.array()[0].msg });
        }
        const {sender_id,receiver_id, type, senderName , image} = req.body;
       
        const uid = sender_id;
        const role = req.body.isPublisher ? Agora.RtcRole.PUBLISHER : Agora.RtcRole.SUBSCRIBER;
        const channel = 'the_medicist'+new Date().getTime();
          
      
        const currentTimestamp = Math.floor(Date.now() / 1000);
        const expirationTimestamp = currentTimestamp + expirationTimeInSeconds;
        const token = Agora.RtcTokenBuilder.buildTokenWithUid(appID, appCertificate, channel, uid, role, expirationTimestamp);
       const role1 = req.body.isPublisher ? Agora.RtcRole.PUBLISHER : Agora.RtcRole.SUBSCRIBER;
        const receiver_token = Agora.RtcTokenBuilder.buildTokenWithUid(appID, appCertificate, channel, receiver_id, role1, expirationTimestamp);
  
        
       
          const receiver = await User.findByPk(receiver_id);
          if (!receiver) {
            return res.status(400).send({message:"Receiver not found."});
          }

            const deviceToken = receiver.device_token;
            
            const receiverId = receiver.id;
            
           
            sendFCMNotification(deviceToken, type, senderName, sender_id , receiverId, channel, token, receiver_token, image , (err , result) => {
                if (err) {
                    console.error('Error sending FCM notification:', err);
                  } else {
                    console.log('Successfully sent FCM notification:', result);
                  }
            });
            return res.status(200).send({channel,token});

        } catch (error) {
            return res.status(200).json({ message: error.message});
        
    }
     
    }
  );

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
server.listen(3003, function() {
    console.log("Server is running on port 3003");
});