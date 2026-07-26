list = [1,2,3,45]

list2 = []

for num in list:
    num *= 2
    list2.append(num)

# 推导式不屑赋值
list3 = [ num ** 2 for num in range(5)]

print(list2, list3)