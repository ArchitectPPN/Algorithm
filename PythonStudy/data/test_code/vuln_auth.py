def delete_user(request, db):
    if request.headers.get("token"):
        user = db.find_user(request.params["id"])
        db.delete(user)
        return "ok"
    return "unauthorized"
