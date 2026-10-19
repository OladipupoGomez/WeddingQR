Deno.serve(async (request) => {
  if (request.method !== "POST") {
    return new Response("Method not allowed", { status: 405 });
  }

  const resendApiKey = Deno.env.get("RESEND_API_KEY");
  const fromEmail = Deno.env.get("RSVP_FROM_EMAIL");
  const functionSecret = Deno.env.get("RSVP_EMAIL_FUNCTION_SECRET");
  if (!functionSecret || request.headers.get("x-rsvp-secret") !== functionSecret) {
    return Response.json({ error: "Unauthorized." }, { status: 401 });
  }
  if (!resendApiKey || !fromEmail) {
    return Response.json({ error: "RSVP email is not configured." }, { status: 500 });
  }

  let payload: {
    event?: string;
    email?: string;
    name?: string;
    attending?: boolean;
    maps_url?: string;
    qr_code_base64?: string;
    qr_code_filename?: string;
  };
  try {
    payload = await request.json();
  } catch {
    return Response.json({ error: "Invalid request body." }, { status: 400 });
  }

  if (!payload.email || !payload.name) {
    return Response.json({ error: "Email and name are required." }, { status: 400 });
  }

  const event = payload.event || "rsvp_confirmation";
  if (event !== "rsvp_confirmation" && event !== "guest_uninvited") {
    return Response.json({ error: "Unsupported email event." }, { status: 400 });
  }

  if (event === "rsvp_confirmation") {
    if (typeof payload.attending !== "boolean" || !payload.qr_code_base64 || !payload.maps_url) {
      return Response.json({ error: "Attendance, QR code, and directions link are required." }, { status: 400 });
    }
    if (!payload.maps_url.startsWith("https://www.google.com/maps/dir/?api=1&destination=")) {
      return Response.json({ error: "Invalid Google Maps directions link." }, { status: 400 });
    }
    if (payload.qr_code_base64.length > 1000000 || !/^[A-Za-z0-9+/]+={0,2}$/.test(payload.qr_code_base64)) {
      return Response.json({ error: "Invalid QR code attachment." }, { status: 400 });
    }
  }

  const escapeHtml = (value: string) => value.replace(/[&<>"']/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  })[character] ?? character);

  const name = escapeHtml(payload.name);
  const isUninvitation = event === "guest_uninvited";
  const response = isUninvitation
    ? "We are writing to let you know that your invitation to the ADE & OLA wedding celebration has been withdrawn. We sincerely apologize for any inconvenience and appreciate your understanding. If you have questions, please contact the wedding hosts."
    : payload.attending
      ? "We have received your RSVP. We look forward to celebrating with you."
      : "We have received your response. We are sorry you cannot join us and will miss you.";
  const subject = isUninvitation
    ? "An update about your ADE & OLA wedding invitation"
    : "Adeola wedding RSVP confirmation";
  const text = isUninvitation
    ? `Dear ${payload.name},\n\n${response}\n\nWith sincere apologies,\nADE & OLA`
    : `Dear ${payload.name},\n\n${response}\nYour personal check-in QR code is attached to this email.\n\nVenue: Zion Hall and Events, Ire-Akari, Isolo, Lagos State\nDirections: ${payload.maps_url}\n\nWith love,\nADE & OLA`;
  const html = isUninvitation
    ? `<p>Dear ${name},</p><p>${escapeHtml(response)}</p><p>With sincere apologies,<br>ADE &amp; OLA</p>`
    : `<p>Dear ${name},</p><p>${escapeHtml(response)}</p><p>Your personal check-in QR code is attached to this email.</p><p><a href="${escapeHtml(payload.maps_url!)}">Get directions to Zion Hall and Events in Google Maps</a></p><p>With love,<br>ADE &amp; OLA</p>`;

  const emailResponse = await fetch("https://api.resend.com/emails", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${resendApiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      from: fromEmail,
      to: [payload.email],
      subject,
      text,
      html,
      ...(!isUninvitation ? { attachments: [{
        filename: payload.qr_code_filename || "adeola-guest-qr.png",
        content: payload.qr_code_base64!,
      }] } : {}),
    }),
  });

  if (!emailResponse.ok) {
    const providerError = await emailResponse.text();
    console.error("[rsvp-email] Resend rejected email", JSON.stringify({
      status: emailResponse.status,
      response: providerError.slice(0, 1000),
    }));
    return Response.json({ error: "Email provider rejected the confirmation." }, { status: 502 });
  }

  return Response.json({ sent: true });
});
