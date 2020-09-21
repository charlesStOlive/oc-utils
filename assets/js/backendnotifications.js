(function () {
    let lastPolledAt = new Date();

    function handleError(err) {
        //console.error(err);
    }

    function loadCount() {
        return fetch('/api/utils/', {
            header: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        })
            .then((res) => {
                if (res.ok) {
                    return res.json();
                } else {
                    throw new Error('Invalid response');
                }
            }, handleError)
            .then((res) => {
                var end = res.end;
                var error = res.error;
                var run = res.run;
                const counterElement = document.querySelector('.counter[data-menu-id="notification"]');
                const btn_task = counterElement.parentElement;
                const icone = counterElement.parentElement.querySelector("a span");

                console.log(res)

                // $allError = run == false && error == false;
                // if ($allError) {
                //   btn_task.classList.add('hide_btn');
                // } else {
                //   btn_task.classList.remove('hide_btn');
                // }
                if (run) {
                    //icone.classList.add('rotate_counter');
                    counterElement.innerHTML = run ? run : '';
                    counterElement.classList.add('counter_run');
                    counterElement.classList.remove('empty');
                } else {
                    counterElement.classList.add('empty');
                    counterElement.classList.remove('counter_run');
                }
                if ((error > 0) || (end > 0)) {
                    let bot = document.getElementById('counter_bot');
                    //console.log(bot)
                    if (!bot) {
                        //console.log("il y a pas de bot")
                        bot = document.createElement("span")
                        bot.classList.add('counter_bot');
                        bot.setAttribute("id", "counter_bot");
                        btn_task.appendChild(bot);
                    }
                    if (error) {
                        bot.classList.add('counter_error');
                        bot.classList.remove('counter_end');
                        bot.innerHTML = error;
                    } else {
                        bot.classList.add('counter_end');
                        bot.classList.remove('counter_error');
                        bot.innerHTML = end;
                    }
                } else {
                    let bot = document.getElementById('counter_bot');
                    //console.log(bot)
                    if (bot) {
                        //console.log("il y a pas de bot")
                        bot.classList.add('empty');
                        bot.classList.remove('counter_run');
                        bot.classList.remove('counter_error');
                    }
                }

                end = null;
                error = null;
                run = null;


            }, handleError);
    }
    loadCount();







    function activityWatcher() {

        //The number of seconds that have passed
        //since the user was active.
        var secondsSinceLastActivity = 0;

        //Five minutes. 60 x 5 = 300 seconds.
        var maxInactivity = (30);

        //Setup the setInterval method to run
        //every second. 1000 milliseconds = 1 second.
        const icone = document.querySelector('.counter[data-menu-id="notification"]').parentElement.querySelector("a span");
        setInterval(function () {
            secondsSinceLastActivity++;
            if (secondsSinceLastActivity < 10) {
                loadCount();
                icone.classList.remove('icone_pause');
            } else {

                icone.classList.add('icone_pause');
            }
        }, 3000);

        //The function that will be called whenever a user is active
        function activity() {
            //reset the secondsSinceLastActivity variable
            //back to 0
            secondsSinceLastActivity = 0;
        }


        //An array of DOM events that should be interpreted as
        //user activity.
        var activityEvents = [
            'mousedown', 'mousemove', 'keydown',
            'scroll', 'touchstart'
        ];

        //add these events to the document.
        //register the activity function as the listener parameter.
        activityEvents.forEach(function (eventName) {
            document.addEventListener(eventName, activity, true);
        });


    }

    activityWatcher();
})();
