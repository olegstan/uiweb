"use strict";
function KeyMap(){

    this.default_language = 'en';
    this.keys = {
        map: {
            // keys
            "3": ["cancel"],
            "8": ["backspace"],
            "9": ["tab"],
            "12": ["clear"],
            "13": ["enter"],
            "16": ["shift"],
            "17": ["ctrl"],
            "18": ["alt", "menu"],
            "19": ["pause", "break"],
            "20": ["capslock"],
            "27": ["escape", "esc"],
            "32": ["space", "spacebar"],
            "33": ["pageup"],
            "34": ["pagedown"],
            "35": ["end"],
            "36": ["home"],
            "37": ["left"],
            "38": ["up"],
            "39": ["right"],
            "40": ["down"],
            "41": ["select"],
            "42": ["printscreen"],
            "43": ["execute"],
            "44": ["snapshot"],
            "45": ["insert", "ins"],
            "46": ["delete", "del"],
            "47": ["help"],
            "91": ["command", "windows", "win", "super", "leftcommand", "leftwindows", "leftwin", "leftsuper"],
            "92": ["command", "windows", "win", "super", "rightcommand", "rightwindows", "rightwin", "rightsuper"],
            "145": ["scrolllock", "scroll"],
            "186": ["semicolon", ";"],
            "187": ["equal", "equalsign", "="],
            "188": ["comma", ","],
            "189": ["dash", "-"],
            "190": ["period", "."],
            "191": ["slash", "forwardslash", "/"],
            "192": ["graveaccent", "`"],
            "219": ["openbracket", "["],
            "220": ["backslash", "\\"],
            "221": ["closebracket", "]"],
            "222": ["apostrophe", "'"],

            // 0-9
            "48": ["zero", "0"],
            "49": ["one", "1"],
            "50": ["two", "2"],
            "51": ["three", "3"],
            "52": ["four", "4"],
            "53": ["five", "5"],
            "54": ["six", "6"],
            "55": ["seven", "7"],
            "56": ["eight", "8"],
            "57": ["nine", "9"],

            //numpad
            "96": ["numzero", "num0"],
            "97": ["numone", "num1"],
            "98": ["numtwo", "num2"],
            "99": ["numthree", "num3"],
            "100": ["numfour", "num4"],
            "101": ["numfive", "num5"],
            "102": ["numsix", "num6"],
            "103": ["numseven", "num7"],
            "104": ["numeight", "num8"],
            "105": ["numnine", "num9"],
            "106": ["nummultiply", "num*"],
            "107": ["numadd", "num+"],
            "108": ["numenter"],
            "109": ["numsubtract", "num-"],
            "110": ["numdecimal", "num."],
            "111": ["numdivide", "num/"],
            "144": ["numlock", "num"],

            //function keys
            "112": ["f1"],
            "113": ["f2"],
            "114": ["f3"],
            "115": ["f4"],
            "116": ["f5"],
            "117": ["f6"],
            "118": ["f7"],
            "119": ["f8"],
            "120": ["f9"],
            "121": ["f10"],
            "122": ["f11"],
            "123": ["f12"]
        },
        macros: [
            //secondary key symbols
            ['shift + `', ["tilde", "~"]],
            ['shift + 1', ["exclamation", "exclamationpoint", "!"]],
            ['shift + 2', ["at", "@"]],
            ['shift + 3', ["number", "#"]],
            ['shift + 4', ["dollar", "dollars", "dollarsign", "$"]],
            ['shift + 5', ["percent", "%"]],
            ['shift + 6', ["caret", "^"]],
            ['shift + 7', ["ampersand", "and", "&"]],
            ['shift + 8', ["asterisk", "*"]],
            ['shift + 9', ["openparen", "("]],
            ['shift + 0', ["closeparen", ")"]],
            ['shift + -', ["underscore", "_"]],
            ['shift + =', ["plus", "+"]],
            ['shift + (', ["opencurlybrace", "opencurlybracket", "{"]],
            ['shift + )', ["closecurlybrace", "closecurlybracket", "}"]],
            ['shift + \\', ["verticalbar", "|"]],
            ['shift + ;', ["colon", ":"]],
            ['shift + \'', ["quotationmark", "\""]],
            ['shift + !,', ["openanglebracket", "<"]],
            ['shift + .', ["closeanglebracket", ">"]],
            ['shift + /', ["questionmark", "?"]]
        ],
        numbers: {
            "48": 0,
            "49": 1,
            "50": 2,
            "51": 3,
            "52": 4,
            "53": 5,
            "54": 6,
            "55": 7,
            "56": 8,
            "57": 9,
            "96": 0,
            "97": 1,
            "98": 2,
            "99": 3,
            "100": 4,
            "101": 5,
            "102": 6,
            "103": 7,
            "104": 8,
            "105": 9
        },
        letters: {
            'en': {
                '65': 'a',
                '66': 'b',
                '67': 'c',
                '68': 'd',
                '69': 'e',
                '70': 'f',
                '71': 'g',
                '72': 'h',
                '73': 'i',
                '74': 'j',
                '75': 'k',
                '76': 'l',
                '77': 'm',
                '78': 'n',
                '79': 'o',
                '80': 'p',
                '81': 'q',
                '82': 'r',
                '83': 's',
                '84': 't',
                '85': 'u',
                '86': 'v',
                '87': 'w',
                '88': 'x',
                '89': 'y',
                '90': 'z'
            }
        }
    };
};

KeyMap.prototype.detectDigit = function(key){
    if((key > 47 && key < 58) || (key > 95 && key < 106)){
        return this.keys.numbers[key];
    }else{
        return false;
    }
};

KeyMap.prototype.detectDelete = function(key){
    if(key == 8 || key == 46){
        return this.keys.map[key];
    }else{
        return false;
    }
};

KeyMap.prototype.detectLetter = function(key){
    if(key > 65 && key < 91){
        return this.keys.letters[this.default_language][key];
    }else{
        return false;
    }
};