from flask import Flask, jsonify, redirect, request, url_for

from catalog import load_catalog

app = Flask(__name__)

placements = []
next_id = 1


@app.get("/")
def index():
    catalog = load_catalog()
    catalog_options = "".join(
        f'<option value="{item["title"]}">'
        f'{item["title"]} — {item["price"] or "price n/a"}</option>'
        for item in catalog
    )
    placement_rows = "".join(
        f"<li>{p['title']} — {p['location']} ({p['status']})</li>"
        for p in placements
    )
    return f"""
    <!doctype html>
    <html><head><title>Palmar</title></head>
    <body style="font-family: sans-serif; max-width: 640px; margin: 4rem auto;">
      <h1>Palmar</h1>
      <p>Art placement tracker.</p>
      <h2>Add placement</h2>
      <form method="post" action="{url_for('add_placement')}">
        <select name="title">{catalog_options}</select><br>
        <input name="location" placeholder="Where is it placed?" required><br>
        <select name="status">
          <option>available</option>
          <option>placed</option>
          <option>sold</option>
        </select><br>
        <button type="submit">Add</button>
      </form>
      <h2>Placements</h2>
      <ul>{placement_rows}</ul>
    </body></html>
    """


@app.post("/placements")
def add_placement():
    global next_id
    form = request.form
    placements.append(
        {
            "id": next_id,
            "title": form.get("title", ""),
            "location": form.get("location", ""),
            "status": form.get("status", "placed"),
        }
    )
    next_id += 1
    return redirect(url_for("index"))


@app.get("/api/placements")
def api_placements():
    return jsonify(placements)


@app.get("/api/catalog")
def api_catalog():
    return jsonify(load_catalog())


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8000)
