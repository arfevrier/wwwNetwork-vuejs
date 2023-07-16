export default {
    isCharNumber(c) {
        return c >= '0' && c <= '9';
    },
    getRandomColor(){
        var letters = '0123456789ABCDEF'.split('');
        var color = '#';
        for (var i = 0; i < 6; i++ ) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    },
}
