<?php
session_start();

// Include database connection and other necessary files
include("../config/connect.php");
include("../config/function.php");

// Initialize variables for subtotal and total
$subtotal = 0;
$total = 0;

// Display cart contents or empty cart message
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
	echo '<h2>Your cart is empty</h2>
        <div class="row mb-5">
            <div class="col-md-6 mb-3 mb-md-0">
                <br><a href="shop.php"><button class="btn btn-black btn-sm btn-block">Continue Shopping</button></a>
            </div>
        </div>';
} else {
	// Check if the checkout form is submitted
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		// Get client information from the form
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		$client_name = $first_name . ' ' . $last_name;
		$wilaya = $_POST['wilaya'];
		$city = $_POST['city'];
		$phone = $_POST['phone'];

		// Initialize points to add for each order
		$pointsToAdd = 5;

		// Store each product in the cart as a separate order
		if (!empty($_SESSION['cart'])) {
			foreach ($_SESSION['cart'] as $product_id => $product) {
				// Fetch product details from database
				$sql = "SELECT product_id, product_name, description, price, image, seller_name, quantity FROM product WHERE product_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("i", $product_id);
				$stmt->execute();
				$result = $stmt->get_result();
				$row = $result->fetch_assoc();

				if ($row) {
					$product_id = $row['product_id'];
					$product_name = $row['product_name'];
					$description = $row['description'];
					$price = $row['price'];
					$image = $row['image'];
					$seller_name = $row['seller_name'];
					$quantity = $product['quantity'];

					// Define default status
					$status = "pending";

					// Insert order into the database
					$sql = "INSERT INTO orders (product_id, name, description, price, image, seller, client_name, city, wilaya, phone, order_date, quantity, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("isssssssssis", $product_id, $product_name, $description, $price, $image, $seller_name, $client_name, $city, $wilaya, $phone, $quantity, $status);
					$stmt->execute();

					// Update vendor points
					$pointsToAdd = 5 * $quantity;
					addVendorPoints($seller_name, $pointsToAdd, $conn);
				}
			}
		}
		updateQuantity($product_id, $quantity, $status, $conn);

		// Clear the cart after placing the order
		unset($_SESSION['cart']);

		// Redirect to the order confirmation page
		header("Location: thankyou.php");
		exit();
	}
}
?>
<!-- Start Header/Navigation -->
<?php include("../inc/header.php") ?>
<!-- End Header/Navigation -->

<!-- Start Form -->
<div class="untree_co-section">
	<div class="container">
		<div class="row">
			<div class="col-md-6 mb-5 mb-md-0">
				<h2 class="h3 mb-3 text-black">Billing Details</h2>
				<div class="p-3 p-lg-5 border bg-white">
					<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
						<div class="form-group row">
							<div class="col-md-6">
								<label for="c_fname" class="text-black">First Name <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_fname" name="first_name" required>
							</div>
							<div class="col-md-6">
								<label for="c_lname" class="text-black">Last Name <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_lname" name="last_name" required>
							</div>
						</div>

						<div class="form-group row">
							<div class="col-md-12">
								<label for="c_address" class="text-black">Address <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_address" name="c_address" placeholder="Street address">
							</div>
						</div>

						<div class="form-group mt-3">
							<input type="text" class="form-control" placeholder="Apartment, suite, unit etc. (optional)">
						</div>

						<div class="form-group row">
							<div class="col-md-6">
								<label for="c_state_country" class="text-black">Wilaya <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_state_country" name="wilaya" required>
							</div>
							<div class="col-md-6">
								<label for="c_postal_zip" class="text-black">City <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_postal_zip" name="city" required>
							</div>
						</div>

						<div class="form-group row mb-5">
							<div class="col-md-6">
								<label for="c_email_address" class="text-black">Posta / Zip <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_email_address" name="c_email_address">
							</div>
							<div class="col-md-6">
								<label for="c_phone" class="text-black">Phone <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="c_phone" name="phone" required placeholder="Phone Number">
							</div>
						</div>

						<div class="form-group mb-5">
							<label for="c_order_notes" class="text-black">Order Notes</label>
							<textarea name="c_order_notes" id="c_order_notes" cols="30" rows="5" class="form-control" placeholder="Write your notes here..."></textarea>
						</div>
						<div class="form-group">
							<input class="btn btn-black btn-lg py-3 btn-block" type="submit" value="Place Order">
						</div>
					</form>
				</div>
			</div>
			<div class="col-md-6">

				<div class="row mb-5">
					<div class="col-md-12">
						<h2 class="h3 mb-3 text-black">Coupon Code</h2>
						<div class="p-3 p-lg-5 border bg-white">

							<label for="c_code" class="text-black mb-3">Enter your coupon code if you have
								one</label>
							<div class="input-group w-75 couponcode-wrap">
								<input type="text" class="form-control me-2" id="c_code" placeholder="Coupon Code" aria-label="Coupon Code" aria-describedby="button-addon2">
								<div class="input-group-append">
									<button class="btn btn-black btn-sm" type="button" id="button-addon2">Apply</button>
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="row mb-5">
					<div class="col-md-12">
						<h2 class="h3 mb-3 text-black">Your Order</h2>
						<div class="p-3 p-lg-5 border bg-white">
							<table class="table site-block-order-table mb-5">
								<thead>
									<tr>
										<th>Product</th>
										<th>Total</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ($_SESSION['cart'] as $product_id => $product) {
										// Fetch product details from database
										$sql = "SELECT product_name, price FROM product WHERE product_id = ?";
										$stmt = $conn->prepare($sql);
										$stmt->bind_param("i", $product_id);
										$stmt->execute();
										$result = $stmt->get_result();
										$row = $result->fetch_assoc();

										if ($row) {
											$product_name = $row['product_name'];
											$price = $row['price'];
											$quantity = $product['quantity'];
											$product_total = $price * $quantity;
											$subtotal += $product_total;
									?>
											<tr>
												<td><?php echo $product_name; ?> <strong class="mx-2">x</strong> <?php echo $quantity; ?></td>
												<td><?php echo '$' . number_format($product_total, 2); ?></td>
											</tr>
									<?php
										}
									}
									?>
									<tr>
										<td class="text-black font-weight-bold"><strong>Order Total</strong></td>
										<td class="text-black font-weight-bold"><strong><?php echo '$' . number_format($subtotal, 2); ?></strong></td>
									</tr>
								</tbody>
							</table>



						</div>
					</div>
				</div>

			</div>
		</div>
		<!-- </form> -->
	</div>
</div>
<!-- End Form -->

<!-- Start Footer Section -->
<?php include("../inc/footer.php") ?>
<!-- End Footer Section -->

<?php
function resetCart()
{
	if (isset($_SESSION['cart'])) {
		unset($_SESSION['cart']); // Unset the cart session variable
		// Alternatively, you can destroy the entire session using session_destroy()
		// session_destroy();
	}
}

// Check if the reset button is clicked
if (isset($_POST['reset_cart'])) {
	resetCart();
}
?>