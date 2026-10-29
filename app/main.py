from flask import Flask, jsonify

app = Flask(__name__)

placements = [
    {"id": 1, "title": "Untitled Blue", "artist": "Self", "location": "Studio wall"},
    {"id": 2, "title": "Harbor Study", "artist": "Self", "location": "Cafe Mocha"},
]

@app.get("/")
def index():
    return """
    <!doctype html>
    <html><head><title>Palmar</title></head>
    <body style="font-family: sans-serif; max-width: 640px; margin: 4rem auto;">
      <h1>Palmar</h1>
      <p>Art placement tracker.</p>
      <h2>Placements</h2>
      <ul>
      """ + "".join(
          f"<li>{p['title']} — {p['location']}</li>" for p in placements
      ) + """
      </ul>
    </body></html>
    """

@app.get("/api/placements")
def api_placements():
    return jsonify(placements)

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8000)
