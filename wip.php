<style>
    .graphWrapper {
        margin-bottom: 100px;
    }
        .yLabelWrapper {
            float: left;
            margin-right: 5px;
            max-width: 30px;
            width: 30px;
            text-align: right;
            overflow: hidden;
            display: none;
        }
        .yLabelWrapper p {
            line-height: {$graphData['settings']['yStep']*20}px;
        }
        .xLabelWrapper {
            position: absolute;
            left: 0;
        }
        .xLabel {
            transform: rotate(-90deg);
            position: absolute;
            top: 0;
            margin-left: 0;
        }
    .diagramWrapper {
        margin-bottom: 50px;
    }
    .legend {
        display: inline-block;
        margin-left: 15px;
    }
    .legend-color {
        width: 7px;
        height: 7px;
        display: inline-block;
        border: 1px solid black;
        margin-right: 5px;
    }

</style>
<div class="graphWrapper" data-render="graph" data-dataset="convPerWeekDay" data-header="Gemiddeld aantal conversaties per weekdag" style="position: relative;"></div>

{*<div class="diagramWrapper" data-render="diagram" data-render-type="circle" data-dataset="convPerEmployee" data-header="Totaal aantal conversaties per medewerker" style="position: relative;"></div>*}

{*<div class="diagramWrapper" data-render="diagram" data-render-type="bar" data-dataset="employeeSatisfaction" data-header="Tevredenheidsscores per medewerker" style="position: relative;"></div>*}

<script>
{literal}
    var graphDataAll = JSON.parse('{/literal}{$graphData|@json_encode}{literal}');
    var diagramDataAll = JSON.parse('{/literal}{$diagramData|@json_encode}{literal}');

    function renderGraph(name,graphData) {
        var yStep = parseInt(graphData['settings']['yMax']) / 18;

        var canvas = document.getElementById(name+"_canvas");
        var context = canvas.getContext('2d');

        /* Draw guidelines */
        /* Vertical */
        var xLowerPos = parseInt(graphData['settings']['height']);
        var xUpperPos = 0;
        var yPos = graphData['settings']['xStep'];
        context.strokeStyle = "black";
        context.beginPath();
        context.moveTo(yPos,xLowerPos);
        context.lineTo(yPos,xUpperPos);
        context.stroke();
        context.strokeStyle = "#efeded";
        yPos = yPos + graphData['settings']['xStep'];
        jQuery.each(graphData['xLabels'], function() {
            context.beginPath();
            context.moveTo(yPos,xLowerPos);
            context.lineTo(yPos,xUpperPos);
            context.stroke();
            yPos = yPos + graphData['settings']['xStep'];
        });
        context.beginPath();
        context.moveTo(yPos,xLowerPos);
        context.lineTo(yPos,xUpperPos);
        context.stroke();

        /* Horizontal */
        var yLeftPos = graphData['settings']['xStep'];
        var yRightPos = parseInt(graphData['settings']['width']);
        var steps = parseInt(graphData['settings']['height']) / 20;
        var xPos = steps;
        var yLabel = Math.ceil(graphData['settings']['yMax'] + yStep);
        for (var i = 0; i <= 19; i++) {
            context.strokeStyle = "#efeded";
            context.beginPath();
            context.moveTo(yLeftPos,xPos);
            context.lineTo(yRightPos,xPos);
            context.stroke();

            if (i<19){
                context.font="10px Arial";
                context.fillText(yLabel,0,xPos + 4);
            }

            xPos = xPos + steps;
            yLabel = Math.ceil(graphData['settings']['yMax'] - yStep*i);
        }
        context.strokeStyle = "black";
        context.beginPath();
        context.moveTo(yLeftPos,parseInt(graphData['settings']['height']));
        context.lineTo(yRightPos,parseInt(graphData['settings']['height']));
        context.stroke();

        /* Draw dots and their interconnecting lines */
        var color = 0;
        jQuery.each(graphData['dots'],function(key,dataSet) {
            var prevX, prevY;
            jQuery.each(dataSet, function (x, y) {
                /* Draw dot */
                context.fillStyle = "hsl(" + color + ",100%,50%)";
                context.fillRect(x, y, 3, 3);
                /* Connect dot with previous dot */
                if (prevX !== "undefined" && prevY !== "undefined") {
                    context.strokeStyle = "hsl(" + color + ",100%,50%)";
                    context.beginPath();
                    context.moveTo(parseInt(prevX)+1, parseInt(prevY)+1);
                    context.lineTo(parseInt(x)+2, parseInt(y)+1);
                    context.stroke();
                }
                prevX = x;
                prevY = y;
            });

            color = color + (360 / graphData['dots'].length);
        });

        /* Create legend */
        var color = 0;
        jQuery.each(graphData['labels'],function(index,label){
            var html = '<span class="legend-color" style="background-color: hsl('+color+',100%,50%);"></span><span class="legend-name">'+label+'</span><br />';
            jQuery("#"+name+"_legend").append(html);
            color = color + (360 / graphData['dots'].length);
        });
    }

    function renderCircleDiagram(name,diagramData) {
        // Canvas settings
        var canvas = document.getElementById("canvas"+name);
        var context = canvas.getContext('2d');
        var centerX = canvas.width / 2;
        var centerY = canvas.height / 2;
        var radius = diagramData['settings']['radius'];
        var startAngle = 0;
        var endAngle;
        var color = 0;

        // Data settings
        var total = diagramData['data']['totals'];

        // Build the circle and contents
        jQuery.each(diagramData['data'], function(itemName,itemValue) {
            if (itemName != "totals") {
                endAngle = startAngle + ((itemValue / total) * (2 * Math.PI));
                context.beginPath();
                context.arc(centerX, centerY, radius, startAngle, endAngle, false);
                context.lineTo(centerX, centerY);
                context.lineTo(centerX, centerY);
                context.closePath();
                context.fillStyle = 'hsl('+color+',100%,50%)';
                context.fill();
                context.lineWidth = 1;
                context.strokeStyle = 'black';
                context.stroke();
                color = color + (360 / diagramData['settings']['count']);
                startAngle = endAngle;
            }
        });

        /* Create legend */
        var color = 0;
        var percentage;
        jQuery.each(diagramData['data'],function(itemName,itemValue){
            if (itemName != "totals") {
                percentage = Math.round(itemValue / total * 100);
                var html = '<span class="legend-color" style="background-color: hsl('
                        +color
                        +',100%,50%);"></span><span class="legend-name">'
                        +itemName
                        +' ('+itemValue+', '+percentage+'%)</span><br />';
                jQuery("#legend" + name).append(html);
                color = color + (360 / diagramData['settings']['count']);
            }
        });
    }

    function renderBarDiagram(name,diagramData){
        // Canvas settings
        var canvas = document.getElementById("canvas"+name);
        var context = canvas.getContext('2d');
        var width = diagramData['settings']['width'];
        var height = diagramData['settings']['height'];

        // how wide will a bar be (excluding margins left and right)?
        var barWidth = width / diagramData['settings']['totalBars'] - 10;

        var color = 0;
        jQuery.each(diagramData['data'], function(itemName,bars) {
            color = 0;
            jQuery.each(bars,function() {
                // Create a bar
                context.beginPath();
                context.rect(188, 50, 200, 100);
                context.fillStyle = 'yellow';
                context.fill();
                context.lineWidth = 1;
                context.strokeStyle = 'black';
                context.stroke();
                color = color + (360 / bars.length);
            });
        });
    }

    /* Render graph inside these divs */
    $("div[data-render='graph']").each(function(){
        var container = $(this);
        var graphData = graphDataAll[container.data("dataset")];

        // Create HTML skeleton
        container.attr("id",container.data("dataset"));
        container.before('<h1>'+container.data("header")+'</h1><br />');
        container.append('<div class="yLabelWrapper" style="height: '+graphData['settings']['height']+'px; max-height: '+graphData['settings']['height']+ 'px;"></div>');
        container.append('<canvas id="'+container.data("dataset")+
                        '_canvas" class="graph" height="'
                        +graphData['settings']['height']
                        +'" width="'
                        +graphData['settings']['width']
                        +'"></canvas>');
        container.append('<div class="xLabelWrapper" style="top: '+(graphData['settings']['height'] + 20)+'px;"></div>');
        container.append('<div id="'+container.data("dataset")+'_legend" class="legend"></div>');

        // Add attributes to the canvas
        var canvas = container.find("canvas");
        canvas.attr("height",graphData['settings']['height']);
        canvas.attr("width",graphData['settings']['width']);

        // Create vertical axis labels
        var yLabelWrapper = container.find(".yLabelWrapper");
        var steps = graphData['settings']['yStep'] * 20;
        var yLabel = graphData['settings']['height'] / graphData['settings']['yStep'];
        for (var i=0; i<= 23; i++) {
            yLabelWrapper.append('<p>'+Math.ceil(yLabel)+'</p>');
            yLabel = yLabel - steps;
        }

        // Create horizontal axis labels
        var xLabelWrapper = container.find(".xLabelWrapper");
        var i = 1;

        for (var j=0;j<graphData['xLabels'].length; j++) {
            var html =      '<div class="xLabel" style="left: '
                            +(i * graphData['settings']['xStep'])+
                            'px; height: '
                            +graphData['settings']['xStep']+
                            'px;"><p>'
                            +graphData["xLabels"][j]+
                            '</p></div>';
            xLabelWrapper.append(html);
            i++;
        }

        renderGraph(container.data("dataset"),graphData);
    });

    /* Render diagram inside these divs */
    $("div[data-render='diagram']").each(function(){
        var container = $(this);
        var diagramData = diagramDataAll[container.data("dataset")];
        var rand = Math.floor((Math.random() * 100000) + 1);
        /* Build HTML skeleton */
        container.before('<h1>'+container.data("header")+'</h1><br />');

        /* What kind of diagram needs to be rendered? */
        if (container.data("render-type") == "circle") {
            container.append('<canvas id="canvas'+rand+'" class="diagram" height="'+2*diagramData['settings']['radius']+'" width="'+2*diagramData['settings']['radius']+'"></canvas>');
            container.append('<div id="legend'+rand+'" class="legend"></div>');
            renderCircleDiagram(rand,diagramData);
        }
        if (container.data("render-type") == "bar") {
            container.append('<canvas id="canvas'+rand+'" class="diagram" height="'+diagramData['settings']['height']+'" width="'+diagramData['settings']['width']+'"></canvas>');
            container.append('<div id="legend'+rand+'" class="legend"></div>');
            renderBarDiagram(rand,diagramData);
        }
    });
{/literal}
</script>