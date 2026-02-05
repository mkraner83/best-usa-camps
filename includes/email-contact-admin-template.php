<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>New Contact Form Submission</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
		<tr>
			<td align="center" style="padding: 20px 10px;">
				<div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
					<div style="background: linear-gradient(135deg, #497C5E 0%, #679B7C 100%); color: #ffffff; padding: 30px 20px; text-align: center;">
						<h1 style="margin: 0; font-size: 28px; font-weight: bold;">✉️ New Contact Form Submission</h1>
					</div>
					<div style="padding: 30px 20px;">
						<h2 style="color: #497C5E; margin-top: 0; font-size: 22px;">Someone Contacted You</h2>
						<p style="margin: 15px 0; font-size: 16px;">Hello Administrator,</p>
						<p style="margin: 15px 0; font-size: 16px;">You have received a new message from your website contact form:</p>
						
						<div style="background: #f8f9fa; border-left: 4px solid #497C5E; padding: 15px; margin: 20px 0; border-radius: 4px;">
							<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Name:</strong> <?php echo esc_html( $first_name . ' ' . $last_name ); ?></p>
							<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Email:</strong> <a href="mailto:<?php echo esc_attr( $email ); ?>" style="color: #497C5E;"><?php echo esc_html( $email ); ?></a></p>
							<?php if ( ! empty( $phone ) ) : ?>
							<p style="margin: 5px 0; font-size: 16px;"><strong style="color: #497C5E;">Phone:</strong> <a href="tel:<?php echo esc_attr( $phone ); ?>" style="color: #333333; text-decoration: none;"><?php echo esc_html( $phone ); ?></a></p>
							<?php endif; ?>
							<p style="margin: 15px 0 5px; font-size: 16px;"><strong style="color: #497C5E;">Message:</strong></p>
							<div style="color: #333333; line-height: 1.6; white-space: pre-wrap; background: #ffffff; padding: 10px; border-radius: 3px;">
<?php echo esc_html( $message ); ?>
							</div>
						</div>
						
						<div style="text-align: center;">
							<a href="mailto:<?php echo esc_attr( $email ); ?>" style="display: inline-block; padding: 14px 30px; background: #497C5E !important; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; text-align: center;">Reply to <?php echo esc_html( $first_name ); ?></a>
						</div>
					</div>
					<div style="background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #e9ecef;">
						<p style="margin: 0;"><strong>Best USA Summer Camps</strong> - Admin Notification</p>
					</div>
				</div>
			</td>
		</tr>
	</table>
</body>
</html>
