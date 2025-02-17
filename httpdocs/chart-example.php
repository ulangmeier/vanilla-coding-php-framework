<?php
	// This is a simple example of how to use Vanilla PHP
	require_once('vanilla.php');

	// Let's load some libraries, specified in /lib/libraries.json:
	libraries("chartjs,animate");

	// Let's begin our business
	BeginBusiness(
		name: "MyApp",
		title: "My Application",
		description: "A brief, catchy phrase that encapsulates the essence of your application"
	);

	// We are ready!
	// Just place your code here...

?>

	<div class="container text-center mt-5 animate__animated animate__pulse roll-in">
		<h1 class="display-4">Chart example</h1>
		<p class="lead">This is a simple Chart Example with ChartJS.</p>
		<a class="btn btn-primary" href="error-example.php">Go to Error Handler Example</a>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var ctx = document.getElementById('myChart').getContext('2d');
			var myChart = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
					datasets: [{
						label: '# of Votes',
						data: [12, 19, 3, 5, 2, 3],
						backgroundColor: [
							'rgba(255, 99, 132, 0.2)',
							'rgba(54, 162, 235, 0.2)',
							'rgba(255, 206, 86, 0.2)',
							'rgba(75, 192, 192, 0.2)',
							'rgba(153, 102, 255, 0.2)',
							'rgba(255, 159, 64, 0.2)'
						],
						borderColor: [
							'rgba(255, 99, 132, 1)',
							'rgba(54, 162, 235, 1)',
							'rgba(255, 206, 86, 1)',
							'rgba(75, 192, 192, 1)',
							'rgba(153, 102, 255, 1)',
							'rgba(255, 159, 64, 1)'
						],
						borderWidth: 1
					}]
				},
				options: {
					scales: {
						y: {
							beginAtZero: true
						}
					}
				}
			});
		});
	</script>

	<div class="container mt-5">
		<canvas id="myChart" width="400" height="200"></canvas>
	</div>