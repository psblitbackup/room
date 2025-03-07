const chatId=123;

//subscribe to agent's private channel
window.Echo.private(`chat.${chatId}`)
.listen('NewMessage',(data)=>{
    console.log('New Message:',data.message);
});