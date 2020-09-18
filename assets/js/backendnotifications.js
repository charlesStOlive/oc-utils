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
        let end = res.end_task;
        let error = res.error;
        let run = res.run;
        const counterElement = document.querySelector('.counter[data-menu-id="notification"]');
        const icone = counterElement.parentElement.querySelector("a span");
        const btn_task = counterElement.parentElement;
        // $allError = run == false && error == false;
        // console.log($allError)
        // if ($allError) {
        //   console.log("on cache")
        //   console.log(btn_task)
        //   btn_task.classList.add('hide_btn');
        // } else {
        //   btn_task.classList.remove('hide_btn');
        // }

        if (run) {
          icone.classList.add('rotate_counter');
          counterElement.innerHTML = run ? run : '';
          counterElement.classList.add('counter_run');
          counterElement.classList.remove('empty');
          counterElement.classList.remove('counter_end');
          counterElement.classList.remove('counter_error');
        } else if (error) {
          counterElement.classList.add('counter_error');
          counterElement.innerHTML = error ? error : '';
          icone.classList.remove('rotate_counter');
          counterElement.classList.remove('empty');
          counterElement.classList.remove('counter_run');
          counterElement.classList.remove('counter_end');
        } else if (end) {
          counterElement.classList.add('counter_end');
          counterElement.innerHTML = end ? end : '';
          icone.classList.remove('rotate_counter');
          counterElement.classList.remove('empty');
          counterElement.classList.remove('counter_error');
          counterElement.classList.remove('counter_run');
        } else {
          counterElement.classList.add('empty');
          icone.classList.remove('rotate_counter');
          counterElement.classList.remove('counter_error');
          counterElement.classList.remove('counter_end');
          counterElement.classList.remove('counter_run');
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
