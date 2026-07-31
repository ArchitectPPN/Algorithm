"""
生成 FastAPI 静态 OpenAPI 文档

用法：
    python3 gen_openapi.py > openapi.json

然后把 openapi.json 导入 Swagger Editor：https://editor.swagger.io
或者用 redoc-cli 生成 HTML：
    npx @redocly/cli build openapi.json -o api-docs.html
"""
import json, sys
sys.path.insert(0, '.')
from main import app

openapi_json = app.openapi()
print(json.dumps(openapi_json, indent=2, ensure_ascii=False))