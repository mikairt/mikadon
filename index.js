<html>
  <body>
    <h1>Cat Clicker</h1>
    <p>random clicker game</p>
    <img src = "https://i.imgur.com/XI5m1Ix.jpg" id = "cat" style = "width: 150px">
    <p>Cats Clicked: <span id = "counter">0</span></p>
    <button id = "reset" onclick = "reset()">Reset</button>
  </body>
  <script>
    var clicks = 0;
    function increment(){
        clicks = clicks + 1;
        counter.innerHTML = clicks;
    }
    function reset(){
        clicks = 0 
        counter.innerHTML = clicks;
    }
  </script>
</html>


