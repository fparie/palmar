from flask import Flask, jsonify, redirect, request, send_from_directory, url_for
from pathlib import Path

from catalog import load_catalog
from store import create_placement, get_placement, init_db, list_placements
from uploads import MAX_CONTENT_LENGTH, is_allowed, safe_name

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = MAX_CONTENT_LENGTH
app.config["UPLOAD_FOLDER"] = "uploads"

init_db()


def _file_url(filename):
    return url_for("uploaded_file", filename=filename)


@app.get("/")
def index():
    catalog = load_catalog()
    catalog_options = "".join(
        f'<option value="{item["title"]}">'
        f'{item["title"]} — {item["price"] or "price n/a"}</option>'
        for item in catalog
    )
    rows = ""
    for p in list_placements():
        photos = " ".join(
            f'<a href="{_file_url(f)}"><img src="{_file_url(f)}" height="80" alt="photo"></a>'
            for f in p["photos"]
        )
        agreement = (
            f'<a href="{_file_url(p["agreement_photo"])}">signed agreement</a>'
            if p["agreement_photo"]
            else "no agreement on file"
        )
        pedestal = "✓ pedestal" if p["pedestal"] else "—"
        rows += f"""
        <tr>
          <td>{p['id']}</td>
          <td><a href="{url_for('placement_detail', placement_id=p['id'])}">{p['title']}</a></td>
          <td>{p['address']}</td>
          <td>{p['contact_name']}<br>{p['contact_phone']}<br>{p['contact_email']}</td>
          <td>{agreement}</td>
          <td>{pedestal}</td>
          <td>{photos}</td>
        </tr>"""
    return f"""
    <!doctype html>
    <html><head><title>Palmar</title></head>
    <body style="font-family: sans-serif; max-width: 960px; margin: 2rem auto;">
      <h1>Palmar</h1>
      <p>Art placement tracker.</p>
      <h2>Add placement</h2>
      <form method="post" action="{url_for('add_placement')}" enctype="multipart/form-data">
        <select name="title" required>{catalog_options}</select><br>
        <input name="address" placeholder="Address" required><br>
        <input name="contact_name" placeholder="Contact person" required><br>
        <input name="contact_phone" placeholder="Phone number" required><br>
        <input name="contact_email" type="email" placeholder="Email" required><br>
        <label><input type="checkbox" name="pedestal" value="1"> Pedestal included</label><br>
        <label>Signed agreement (photo/scan):</label>
        <input type="file" name="agreement_photo" accept="image/*,.pdf"><br>
        <label>Placement photos:</label>
        <input type="file" name="photos" multiple accept="image/*"><br>
        <button type="submit">Add</button>
      </form>
      <h2>Placements</h2>
      <table border="1" cellpadding="6" style="border-collapse: collapse;">
        <tr><th>ID</th><th>Piece</th><th>Address</th><th>Contact</th><th>Agreement</th><th>Pedestal</th><th>Photos</th></tr>
        {rows}
      </table>
    </body></html>
    """


@app.post("/placements")
def add_placement():
    fields = {
        "title": request.form.get("title", ""),
        "address": request.form.get("address", ""),
        "contact_name": request.form.get("contact_name", ""),
        "contact_phone": request.form.get("contact_phone", ""),
        "contact_email": request.form.get("contact_email", ""),
        "pedestal": request.form.get("pedestal"),
    }
    if not all([fields["title"], fields["address"], fields["contact_name"],
                fields["contact_phone"], fields["contact_email"]]):
        return "Missing required fields", 400

    upload_dir = Path(app.config["UPLOAD_FOLDER"])
    upload_dir.mkdir(exist_ok=True)

    agreement_photo = None
    file = request.files.get("agreement_photo")
    if file and file.filename and is_allowed(file.filename):
        agreement_photo = safe_name(file.filename)
        file.save(upload_dir / agreement_photo)

    photos = []
    for file in request.files.getlist("photos"):
        if file and file.filename and is_allowed(file.filename):
            filename = safe_name(file.filename)
            file.save(upload_dir / filename)
            photos.append(filename)

    placement_id = create_placement(fields, agreement_photo, photos)
    return redirect(url_for("placement_detail", placement_id=placement_id))


@app.get("/placements/<int:placement_id>")
def placement_detail(placement_id):
    p = get_placement(placement_id)
    if p is None:
        return "Placement not found", 404
    photos = "".join(
        f'<p><img src="{_file_url(f)}" height="300" alt="photo"></p>'
        for f in p["photos"]
    )
    agreement = (
        f'<a href="{_file_url(p["agreement_photo"])}">View signed agreement</a>'
        if p["agreement_photo"]
        else "No agreement on file"
    )
    return f"""
    <!doctype html>
    <html><head><title>Palmar — {p['title']}</title></head>
    <body style="font-family: sans-serif; max-width: 640px; margin: 2rem auto;">
      <p><a href="{url_for('index')}">&larr; All placements</a></p>
      <h1>{p['title']}</h1>
      <p>Address: {p['address']}</p>
      <p>Contact: {p['contact_name']} — {p['contact_phone']} — {p['contact_email']}</p>
      <p>Pedestal: {'✓ included' if p['pedestal'] else 'not included'}</p>
      <p>Agreement: {agreement}</p>
      <h2>Placement photos</h2>
      {photos or '<p>No photos yet.</p>'}
    </body></html>
    """


@app.get("/api/placements")
def api_placements():
    placements = []
    for p in list_placements():
        placements.append({
            **p,
            "agreement_photo_url": _file_url(p["agreement_photo"]) if p["agreement_photo"] else None,
            "photo_urls": [_file_url(f) for f in p["photos"]],
        })
    return jsonify(placements)


@app.get("/api/placements/<int:placement_id>")
def api_placement(placement_id):
    p = get_placement(placement_id)
    if p is None:
        return jsonify({"error": "not found"}), 404
    p["agreement_photo_url"] = _file_url(p["agreement_photo"]) if p["agreement_photo"] else None
    p["photo_urls"] = [_file_url(f) for f in p["photos"]]
    return jsonify(p)


@app.get("/api/catalog")
def api_catalog():
    return jsonify(load_catalog())


@app.get("/uploads/<path:filename>")
def uploaded_file(filename):
    return send_from_directory(app.config["UPLOAD_FOLDER"], filename)


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8000)
