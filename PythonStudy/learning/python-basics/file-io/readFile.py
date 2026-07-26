try:
    with open('example.txt', 'r') as file:
        content = file.read()
        print(content)
except FileNotFoundError:
    print("文件不存在")
except IOError:
    print("读取失败")
